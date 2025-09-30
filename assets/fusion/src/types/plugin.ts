import ConfigBuilder from '../builder/ConfigBuilder';
import { MaybePromise } from '../types/index.ts';
import { PluginOption } from 'vite';

export type FusionPlugin = PluginOption & {
  buildConfig?: (builder: ConfigBuilder) => MaybePromise<any>;
}
