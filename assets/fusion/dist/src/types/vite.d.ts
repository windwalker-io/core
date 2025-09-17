import { FusionPlugin } from './plugin.ts';
import { MaybePromise } from 'rollup';
export interface FusionVitePluginOptions {
    fusionfile?: string | Fusionfile;
    plugins?: FusionPlugin[];
    cwd?: string;
}
export type FusionVitePluginUnresolved = FusionVitePluginOptions | string | (() => MaybePromise<Record<string, any>>);
export type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);
