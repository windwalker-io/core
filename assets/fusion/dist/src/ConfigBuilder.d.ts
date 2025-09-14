import { PreRenderedAsset, PreRenderedChunk } from 'rollup';
import { ConfigEnv, PluginOption, UserConfig } from 'vite';
export default class ConfigBuilder {
    config: UserConfig;
    env: ConfigEnv;
    entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string)[];
    chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string)[];
    assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string)[];
    constructor(config: UserConfig, env: ConfigEnv);
    merge(override: UserConfig | ((config: UserConfig) => UserConfig)): this;
    private getDefaultOutput;
    ensurePath(path: string, def?: any): this;
    get(path: string): any;
    set(path: string, value: any): this;
    addInput(input: string, group?: string): this;
    addPlugin(plugin: PluginOption): void;
    removePlugin(plugin: string | PluginOption): void;
}
