import { RunnerCliParams } from './types';
import { PreRenderedAsset, PreRenderedChunk } from 'rollup';
import { ConfigEnv, PluginOption, UserConfig } from 'vite';
export default class ConfigBuilder {
    config: UserConfig;
    env: ConfigEnv;
    params: RunnerCliParams;
    entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[];
    chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[];
    assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string | undefined | void)[];
    fileNameMap: Record<string, string>;
    constructor(config: UserConfig, env: ConfigEnv, params: RunnerCliParams);
    merge(override: UserConfig | ((config: UserConfig) => UserConfig)): this;
    mapFilename(id: string, output: string): this;
    private getDefaultOutput;
    ensurePath(path: string, def?: any): this;
    get(path: string): any;
    set(path: string, value: any): this;
    toFileId(input: string, group?: string): string;
    addInput(input: string, group?: string): this;
    addPlugin(plugin: PluginOption): void;
    removePlugin(plugin: string | PluginOption): void;
    relativePath(to: string): string;
    debug(): void;
}
