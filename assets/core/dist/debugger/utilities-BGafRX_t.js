System.register([], (function(exports, module) {
  "use strict";
  return {
    execute: (function() {
      exports({
        h: httpStatusColor,
        s: stateColor
      });
      function stateColor(value, avg) {
        if (value > avg * 2) {
          return "danger";
        } else if (value > avg * 1.5) {
          return "warning";
        } else if (value < avg / 2) {
          return "success";
        } else {
          return "info";
        }
      }
      function httpStatusColor(status) {
        if (status >= 300 && status < 400) {
          return "info";
        }
        if (status >= 400 && status < 500) {
          return "warning";
        }
        if (status >= 200 && status < 300) {
          return "success";
        }
        return "danger";
      }
    })
  };
}));
