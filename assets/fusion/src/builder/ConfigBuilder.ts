import BuildTask from '@/builder/BuildTask.ts';
import { RunnerCliParams } from '@/types';
import { show } from '@/utilities/utilities.ts';
import { get, set } from 'lodash-es';
import { isAbsolute, relative } from 'node:path';
import { MaybePromise, PreRenderedAsset, PreRenderedChunk, RollupOptions } from 'rollup';
import { ConfigEnv, mergeConfig, PluginOption, UserConfig } from 'vite';

export default class ConfigBuilder {
  static globalOverrideConfig: UserConfig = {};
  overrideConfig: UserConfig = {};

  entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[] = [];
  chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[] = [];
  assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string | undefined | void)[] = [];

  moveFilesMap: Record<string, string> = {};
  copyFilesMap: Record<string, string> = {};
  deleteFilesMap: Record<string, string> = {};
  postBuildCallbacks: (() => MaybePromise<void>)[] = [];
  // fileNameMap: Record<string, string> = {};

  // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
  cleans: string[] = [];

  tasks: Map<string, BuildTask> = new Map();

  constructor(public config: UserConfig, public env: ConfigEnv, public params: RunnerCliParams) {
    // this.ensurePath('build', {});
    // this.ensurePath('build.rollupOptions', {
    //   input: {},
    //   output: this.getDefaultOutput(),
    // });
    // this.ensurePath('plugins', []);

    this.config = mergeConfig<UserConfig, UserConfig>(this.config, {
      build: {
        rollupOptions: {
          preserveEntrySignatures: 'strict',
          input: {},
          output: this.getDefaultOutput(),
          // external: (source: string, importer: string | undefined, isResolved: boolean) => {
          //   for (const external of this.externals) {
          //     const result = external(source, importer, isResolved);
          //
          //     if (result) {
          //       return true;
          //     }
          //   }
          // },
        },
        emptyOutDir: false,
        sourcemap: env.mode !== 'production' ? 'inline' : false,
      },
      plugins: [],
      css: {
        devSourcemap: true,
      },
      esbuild: {
        // Todo: Remove if esbuild supports decorators by default
        target: 'es2022',
      }
    });
  }

  merge(override: UserConfig | ((config: UserConfig) => UserConfig)) {
    if (typeof override === 'function') {
      this.config = override(this.config) ?? this.config;

      return this;
    }

    this.config = mergeConfig(this.config, override);

    return this;
  }

  private getDefaultOutput(): RollupOptions['output'] {
    return {
      entryFileNames: (chunkInfo) => {
        const name = this.getChunkNameFromTask(chunkInfo);

        if (name) {
          return name;
        }

        for (const entryFileNamesCallback of this.entryFileNamesCallbacks) {
          const name = entryFileNamesCallback(chunkInfo);

          if (name) {
            return name;
          }
        }

        // console.log(chunkInfo, this.relativePath(chunkInfo.facadeModuleId));

        return '[name].js';
      },
      chunkFileNames: (chunkInfo) => {
        const name = this.getChunkNameFromTask(chunkInfo);

        if (name) {
          return name;
        }

        for (const chunkFileNamesCallback of this.chunkFileNamesCallbacks) {
          const name = chunkFileNamesCallback(chunkInfo);

          if (name) {
            return name;
          }
        }

        return 'chunks/[name]-[hash].js';
      },
      assetFileNames: (assetInfo) => {
        // if (this.fileNameMap[assetInfo.name]) {
        //   assetInfo.name = this.fileNameMap[assetInfo.name];
        //   return assetInfo.name;
        // }

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

  private getChunkNameFromTask(chunkInfo: PreRenderedChunk) {
    if (this.tasks.has(chunkInfo.name)) {
      const output = this.tasks.get(chunkInfo.name)?.output;

      if (output) {
        const name = typeof output === 'function' ? output(chunkInfo) : output;

        if (!isAbsolute(name)) {
          return name;
        }
      }
    }

    return undefined;
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

  addTask(input: string, group?: string) {
    const task = new BuildTask(input, group);

    this.tasks.set(task.id, task);

    const inputOptions = this.config.build!.rollupOptions!.input! as Record<string, string>;
    inputOptions[task.id] = task.input;

    return task;
  }

  // addExternals(externals: Externalize) {
  //   if (Array.isArray(externals)) {
  //     this.externals.push((rollupOptions) => {
  //       rollupOptions.external
  //     })
  //   } else if (typeof externals === 'object') {
  //
  //   } else {
  //
  //   }
  // }

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

  relativePath(to: string) {
    return relative(process.cwd(), to);
  }

  debug() {
    show(this.config);
  }
}
