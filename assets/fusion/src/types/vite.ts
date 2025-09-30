import { FusionPlugin } from '../types/plugin.ts';
import { RunnerCliParams } from '../types/runner.ts';
import { MaybePromise } from '../types/index.ts';

export interface FusionPluginOptions {
  fusionfile?: string | Fusionfile;
  chunkDir?: string;
  chunkNameObfuscation?: boolean;
  plugins?: FusionPlugin[];
  cliParams?: RunnerCliParams;
}

export type FusionPluginOptionsUnresolved = FusionPluginOptions | string | (() => MaybePromise<Record<string, any>>);

export type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);
