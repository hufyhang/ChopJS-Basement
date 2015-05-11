/* global $ch, $$CHOP */
$ch.define('basement', function () {
  'use strict';
  var URL = 'http://feifeihang.info/basement/php/basement.php';
  var lastModified = {};

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

    pull: function (appName, password, version, callback, errorCb) {
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

    pullKey: function (appName, password, version, key, callback, errorCb) {
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


    push: function (appName, password, version, json, callback) {
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

    update: function (appName, password, version, json, callback) {
      json = JSON.stringify(json);
      $$CHOP.http(URL, {
        method: 'post',
        data: {
          option: 'update',
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

    watch: function (appName, password, version, interval, callback) {
      var that = this;
      window.setInterval(function () {
        that.pull(appName, password, version, function (res) {
          if (res.LastModified !== lastModified[appName]) {
            lastModified[appName] = res.LastModified;
            if (callback) {
              callback(res);
            }
          }
        });
      }, interval);
    },

    keyBuffer: {},
    watchKey: function (appName, password, version, key, interval, callback) {
      this.keyBuffer[appName] = {};
      var that = this;
      window.setInterval(function () {
        that.pullKey(appName, password, version, key, function (res) {
          if (that.keyBuffer[appName][key] !== res.data) {
            that.keyBuffer[appName][key] = res.data;
            if (callback) {
              callback(res);
            }
          }
        });
      }, interval);
    }

  };
});