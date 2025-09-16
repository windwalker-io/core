import { LoadedConfigTask } from '@/types/runner';
import Module from 'module';
import { MaybePromise } from 'rollup';

export interface FusionVitePluginOptions {
  fusionfile?: string | Fusionfile;
  cwd?: string;
}

export type FusionVitePluginUnresolved = FusionVitePluginOptions | string | (() => MaybePromise<Record<string, any>>);

export type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);
