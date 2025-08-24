'use strict';

Object.defineProperty(exports, '__esModule', { value: true });

var fusion = require('@/dep');
var runner = require('@/runner');

function _interopNamespaceDefault(e) {
    var n = Object.create(null);
    if (e) {
        Object.keys(e).forEach(function (k) {
            if (k !== 'default') {
                var d = Object.getOwnPropertyDescriptor(e, k);
                Object.defineProperty(n, k, d.get ? d : {
                    enumerable: true,
                    get: function () { return e[k]; }
                });
            }
        });
    }
    n.default = e;
    return Object.freeze(n);
}

var fusion__namespace = /*#__PURE__*/_interopNamespaceDefault(fusion);

const isVerbose = runner.params.verbose ? runner.params.verbose > 0 : false;
var index = {
    ...fusion__namespace,
    params: runner.params,
};

Object.defineProperty(exports, "params", {
    enumerable: true,
    get: function () { return runner.params; }
});
exports.default = index;
exports.isVerbose = isVerbose;
Object.keys(fusion).forEach(function (k) {
    if (k !== 'default' && !Object.prototype.hasOwnProperty.call(exports, k)) Object.defineProperty(exports, k, {
        enumerable: true,
        get: function () { return fusion[k]; }
    });
});
//# sourceMappingURL=index.cjs.map
