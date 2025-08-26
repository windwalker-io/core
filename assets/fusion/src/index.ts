
export * from '@/dep';
import * as fusion from '@/dep';
import { parseArgv, run } from '@/runner/run';
import { RunnerCliParams } from '@/types/runner';
import { fileURLToPath } from 'node:url';

let params: RunnerCliParams | undefined = undefined;

const isCliRunning = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];

export default {
  ...fusion,
  params,
};

if (isCliRunning) {
  params = parseArgv();

  run(params);
}

const isVerbose = params?.verbose ? params?.verbose > 0 : false;

export { isVerbose };
