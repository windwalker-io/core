import { OverrideOptions, TaskInput } from '../types';
import { MaybeArray, OutputOptions } from 'rollup';
import { LibraryOptions, PluginOption, UserConfig } from 'vite';
export declare function defineAllConfigs(configs: UserConfig[]): import('vite').UserConfigExport[];
export declare function createViteLibOptions(input: TaskInput, extraOptions?: OverrideOptions<LibraryOptions>): LibraryOptions;
export declare function createViteOptions(lib?: LibraryOptions, output?: MaybeArray<OutputOptions>, plugins?: PluginOption[], override?: OverrideOptions<UserConfig>): Partial<UserConfig>;
