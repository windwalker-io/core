import { FusionVitePluginOptions } from './types';
import { PluginOption } from 'vite';
export * from './dep';
import * as fusion from '@/dep';
export default fusion;
export declare function useFusion(options?: FusionVitePluginOptions): PluginOption;
