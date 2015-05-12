/* global $ch */
'use strict';

var PHP = '//feifeihang.info/basement/php/portal.php';
$ch.use(['./chop-bundle'], function () {
  intialize();
});

function intialize() {
  $ch.scope('appScope', false, function ($scope, $event) {
    $event.listen('liveSearch', function (evt) {
      var items = $ch.source('items');
      var keyword = $scope.name.get() || '';
      keyword = keyword.trim();
      if (keyword === '') {
        $scope.container.inline(items);
        return;
      }


      if (evt.keyCode === 13) {
        items = $ch.filter(items, function (item) {
          item.name += '';
          if (item.name.toUpperCase() === keyword.toUpperCase()) {
            return true;
          }
        });
        if (items.length > 0) {
          $scope.container.inline(items);
        } else {
          $scope.container.html('<input type="password" id="password" placeholder="password"/> \
                                <div class="create-btn" onclick="create();">create "'
                                + keyword + '"</div>');
        }

        return;
      }

      items = $ch.filter(items, function (item) {
        item.name += '';
        if (item.name.toUpperCase().indexOf(keyword.toUpperCase()) !== -1) {
          return true;
        }
      });
      if (items.length > 0) {
        $scope.container.inline(items);
      } else {
        $scope.container.html('<input type="password" id="password" placeholder="password"/> \
                              <div class="create-btn" onclick="create();">create "'
                              + keyword + '"</div>');
      }
    });


    $ch.http(PHP, {
      done: function (res) {
        retrieveApps(res, $scope);
      }
    });
  });
}

function create() {
  var password = $ch.find('#password').val() || '';
  var name = $ch.find('#name-input').val() || '';
  name = name.trim();
  if (name !== '') {
    $ch.basement.create(name, password, function () {
      intialize();
    });
  }
}


function retrieveApps(res, $scope) {
  res = JSON.parse(res.data);
  if (res.code === 200) {
    var items = res.items;
    items = items.map(function (item) {
      var option = $ch.element('option');
      option.attr('value', item);

      console.log($scope);
      $scope.datalist.appendChild(option);
      return {name: item};
    });

    $ch.source('items', items);

    $scope.container.inline(items);
  }
}