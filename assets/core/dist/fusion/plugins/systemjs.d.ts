import { OutputAsset, OutputChunk } from 'rollup';
import { PluginOption } from 'vite';
export declare function injectSystemJS(systemPath?: string, filter?: (file: OutputAsset | OutputChunk) => any): PluginOption;
export declare function systemCSSFix(): PluginOption;
