import { RunnerCliParams } from './types';
declare let params: RunnerCliParams | undefined;
export declare function prepareParams(p: RunnerCliParams): RunnerCliParams;
declare let isVerbose: boolean;
declare const isProd: boolean;
declare const isDev: boolean;
export { isVerbose, isDev, isProd, params };
