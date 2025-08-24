import * as fusion from '@/dep';
export * from '@/dep';
import { parseArgv, run } from '@/runner/run';
import { fileURLToPath } from 'node:url';

let params = undefined;
const isCliRunning = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];
var index = {
    ...fusion,
    params,
};
if (isCliRunning) {
    params = parseArgv();
    run(params);
}
const isVerbose = params?.verbose ? params?.verbose > 0 : false;

export { index as default, isVerbose };
//# sourceMappingURL=index.js.map
