import ConfigBuilder from '@/builder/ConfigBuilder';
import { MaybePromise } from 'rollup';
import { PluginOption } from 'vite';

export type FusionPlugin = PluginOption & {
  buildConfig?: (builder: ConfigBuilder) => MaybePromise<any>;
}
