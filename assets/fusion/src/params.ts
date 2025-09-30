import { RunnerCliParams } from './types';

let params: RunnerCliParams | undefined = undefined;

export function prepareParams(p: RunnerCliParams): RunnerCliParams {
  params = p;

  isVerbose = params?.verbose ? params?.verbose > 0 : false;

  return p;
}

let isVerbose = false;
const isProd = process.env.NODE_ENV === 'production';
const isDev = !isProd;

export { isVerbose, isDev, isProd, params };
