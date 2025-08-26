import { parseArgv, runApp } from '@/runner/app';

export const params = parseArgv();

export default {
  params,
};

runApp(params);
