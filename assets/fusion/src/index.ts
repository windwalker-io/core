
export * from '@/dep';
import * as fusion from '@/dep';
import { parseArgv, runApp } from '@/runner/app';
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

  runApp(params);
}

const isVerbose = params?.verbose ? params?.verbose > 0 : false;

export { isVerbose };
