import { MaybeArray, MaybePromise, RollupOptions } from 'rollup';
import { Arguments } from 'yargs';

export type RunnerCliOptions = {
  w?: boolean;
  watch?: boolean;
  cwd?: string;
  l?: boolean;
  list?: boolean;
  c?: string;
  config?: string;
  v?: number;
  verbose?: number;
}
export type RunnerCliParams = Arguments<RunnerCliOptions>;

export interface ConfigResult {
  path: string;
  filename: string;
  type: 'commonjs' | 'module' | 'unknown';
  ts: boolean;
}

export type LoadedConfigTask = MaybeArray<MaybePromise<RollupOptions> | (() => MaybePromise<MaybeArray<MaybePromise<RollupOptions>>>)>;
