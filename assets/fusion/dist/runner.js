import { parseArgv, run } from '@/runner/run';

const params = parseArgv();
var runner = {
    params,
};
run(params);

export { runner as default, params };
//# sourceMappingURL=runner.js.map
