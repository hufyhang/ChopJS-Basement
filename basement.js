/* global $ch, $$CHOP */
$ch.define('basement', function () {
  'use strict';
  var URL = 'http://feifeihang.info/basement/db';

  function interpretName(appName) {
    var tokens = appName.split('::');
    if (tokens.length < 2) {
      tokens.push(0);
    }

    return {
      appName: tokens[0],
      version: tokens[1] + ''
    };
  }

  $$CHOP.basement = {};
  $$CHOP.basement = {
    top: function (appName, password, callback, errorCb) {
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'top',
          app: appName,
          password: password
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (res.code === 200) {
            if (callback) {
              callback();
            }
          } else if (errorCb) {
            errorCb(res);
          }
        }
      });
    },

    upgrade: function (appName, password, callback, errorCb) {
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'upgrade',
          app: appName,
          password: password
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (res.code === 200) {
            if (callback) {
              callback();
            }
          } else if (errorCb) {
            errorCb(res);
          }
        }
      });
    },

    create: function (appName, password, callback, errorCb) {
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'create',
          app: appName,
          password: password
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (res.code === 200) {
            if (callback) {
              callback();
            }
          } else if (errorCb) {
            errorCb(res);
          }
        }
      });
    },

    pull: function (appName, password, callback, errorCb) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'pull',
          app: appName,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (res.code === 200) {
            res.json = JSON.parse(decodeURIComponent(res.json));
            if (callback) {
              callback(res);
            }
          } else if (errorCb) {
            errorCb(res);
          }
        }
      });
    },

    pullKey: function (appName, password, key, callback, errorCb) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'pull key',
          app: appName,
          key: key,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (res.code === 200) {
            res.json = JSON.parse(decodeURIComponent(res.json));
            if (callback) {
              callback(res);
            }
          } else if (errorCb) {
            errorCb(res);
          }
        }
      });
    },


    push: function (appName, password, json, callback) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      json = JSON.stringify(json);
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'push',
          app: appName,
          json: json,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (callback) {
            callback(res);
          }
        }
      });

    },

    update: function (appName, password, key, json, callback) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      json = JSON.stringify(json);
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'update',
          app: appName,
          key: key,
          json: json,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (callback) {
            callback(res);
          }
        }
      });
    },

    remove: function (appName, password, key, callback) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'remove',
          app: appName,
          key: key,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          if (callback) {
            callback(res);
          }
        }
      });
    },

    watch: function (appName, password, key, callback) {
      var tks = interpretName(appName);
      appName = tks.appName;
      var version = tks.version;

      var that = this;

      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'watch',
          app: appName,
          key: key,
          password: password,
          version: version
        },
        done: function (res) {
          res = JSON.parse(res.responseText);
          callback(res);
          that.watchKey(appName, password, key, callback);
        }
      });
    }

  };
});