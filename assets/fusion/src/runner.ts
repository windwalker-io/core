import { parseArgv, run } from '@/runner/run';

export const params = parseArgv();

export default {
  params,
};

run(params);
