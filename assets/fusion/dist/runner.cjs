'use strict';

Object.defineProperty(exports, '__esModule', { value: true });

var run = require('@/runner/run');

const params = run.parseArgv();
var runner = {
    params,
};
run.run(params);

exports.default = runner;
exports.params = params;
//# sourceMappingURL=runner.cjs.map
