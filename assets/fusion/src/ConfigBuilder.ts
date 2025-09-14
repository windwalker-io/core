import { shortHash } from '@/utilities/crypto.ts';
import { mergeOptions } from '@/utilities/utilities.ts';
import { get, set, uniqueId } from 'lodash-es';
import { normalize } from 'node:path';
import { PreRenderedAsset, PreRenderedChunk, RollupOptions } from 'rollup';
import { ConfigEnv, mergeConfig, PluginOption, UserConfig } from 'vite';

export default class ConfigBuilder {
  entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string)[] = [];
  chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string)[] = [];
  assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string)[] = [];

  constructor(public config: UserConfig, public env: ConfigEnv) {
    // this.ensurePath('build', {});
    // this.ensurePath('build.rollupOptions', {
    //   input: {},
    //   output: this.getDefaultOutput(),
    // });
    // this.ensurePath('plugins', []);

    this.config = mergeConfig(this.config, {
      build: {
        rollupOptions: {
          input: {},
          output: this.getDefaultOutput(),
        }
      },
      plugins: [],
    });
  }

  merge(override: UserConfig | ((config: UserConfig) => UserConfig)) {
    this.config = mergeOptions(this.config, override);

    return this;
  }

  private getDefaultOutput(): RollupOptions['output'] {
    return {
      entryFileNames: (chunkInfo) => {
        for (const entryFileNamesCallback of this.entryFileNamesCallbacks) {
          const name = entryFileNamesCallback(chunkInfo);

          if (name) {
            return name;
          }
        }
        return '[name].js';
      },
      chunkFileNames: (chunkInfo) => {
        for (const chunkFileNamesCallback of this.chunkFileNamesCallbacks) {
          const name = chunkFileNamesCallback(chunkInfo);

          if (name) {
            return name;
          }
        }
        return '[name].js';
      },
      assetFileNames: (assetInfo) => {
        for (const assetFileNamesCallback of this.assetFileNamesCallbacks) {
          const name = assetFileNamesCallback(assetInfo);

          if (name) {
            return name;
          }
        }

        return '[name].[ext]';
      }
    };
  }

  ensurePath(path: string, def: any = {}) {
    if (get(this.config, path) == null) {
      set(this.config, path, def);
    }

    return this;
  }

  get(path: string) {
    return get(this.config, path);
  }

  set(path: string, value: any) {
    set(this.config, path, value);
    return this;
  }

  addInput(input: string, group?: string) {
    input = normalize(input);

    group ||= uniqueId();
    const id = group + '-' + shortHash(input);

    (this.config.build!.rollupOptions!.input as Record<string, string>)[id] = input;

    return this;
  }

  addPlugin(plugin: PluginOption) {
    this.config.plugins?.push(plugin);
  }

  removePlugin(plugin: string | PluginOption) {
    this.config.plugins = this.config.plugins?.filter((p) => {
      if (!p) {
        return true;
      }

      if (typeof plugin === 'string' && typeof p === 'object' && 'name' in p) {
        return p.name !== plugin;
      } else if (typeof plugin === 'object' && typeof p === 'object') {
        return p !== plugin;
      }

      return true;
    });
  }
}
