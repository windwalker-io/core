'use strict';

Object.defineProperty(exports, '__esModule', { value: true });

var fusion = require('@/dep');
var run = require('@/runner/run');
var node_url = require('node:url');

var _documentCurrentScript = typeof document !== 'undefined' ? document.currentScript : null;
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

let params = undefined;
const isCliRunning = process.argv[1] && node_url.fileURLToPath((typeof document === 'undefined' ? require('u' + 'rl').pathToFileURL(__filename).href : (_documentCurrentScript && _documentCurrentScript.tagName.toUpperCase() === 'SCRIPT' && _documentCurrentScript.src || new URL('index.cjs', document.baseURI).href))) === process.argv[1];
var index = {
    ...fusion__namespace,
    params,
};
if (isCliRunning) {
    params = run.parseArgv();
    run.run(params);
}
const isVerbose = params?.verbose ? params?.verbose > 0 : false;

exports.default = index;
exports.isVerbose = isVerbose;
Object.keys(fusion).forEach(function (k) {
    if (k !== 'default' && !Object.prototype.hasOwnProperty.call(exports, k)) Object.defineProperty(exports, k, {
        enumerable: true,
        get: function () { return fusion[k]; }
    });
});
//# sourceMappingURL=index.cjs.map
