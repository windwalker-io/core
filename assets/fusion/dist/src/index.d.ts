import { FusionVitePluginOptions } from './types';
import { PluginOption, UserConfig } from 'vite';
export * from './dep';
import * as fusion from '@/dep';
export default fusion;
export declare function useFusion(options?: FusionVitePluginOptions): PluginOption;
export declare function mergeViteConfig(config: UserConfig): void;
export declare function outDir(outDir: string): void;
