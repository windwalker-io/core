import { FusionPlugin } from './plugin.ts';
import { RunnerCliParams } from './runner';
import { MaybePromise } from 'rollup';
export interface FusionVitePluginOptions {
    fusionfile?: string | Fusionfile;
    chunkDir?: string;
    plugins?: FusionPlugin[];
    cliParams?: RunnerCliParams;
}
export type FusionVitePluginUnresolved = FusionVitePluginOptions | string | (() => MaybePromise<Record<string, any>>);
export type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);
