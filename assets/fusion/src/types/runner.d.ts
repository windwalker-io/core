import { ProcessorInterface } from '@/processors/ProcessorInterface.ts';
import { MaybeArray, MaybePromise } from 'rollup';
import { UserConfig } from 'vite';
import { Arguments } from 'yargs';

export type RunnerCliOptions = {
  // w?: boolean;
  // watch?: boolean;
  cwd?: string;
  l?: boolean;
  list?: boolean;
  c?: string;
  config?: string;
  v?: number;
  verbose?: number;
  // series?: boolean;
  // s?: boolean;
}
export type RunnerCliParams = Arguments<RunnerCliOptions>;

export interface ConfigResult {
  path: string;
  filename: string;
  type: 'commonjs' | 'module' | 'unknown';
  ts: boolean;
}

export type LoadedConfigTask = MaybeArray<MaybePromise<ProcessorInterface> | (() => LoadedConfigTask)>;

export type RunningTasks = Record<string, ProcessorInterface[]>;

// export type RunningTask = {
//   name: string;
//   index: number;
//   options: UserConfig;
// };
