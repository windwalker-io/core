export * from '@/dep';
import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { prepareParams } from '@/params';
import { getArgsAfterDoubleDashes, parseArgv } from '@/runner/app';
import { expandModules, loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe.ts';
import { resolveAllTasksAsProcessors, selectRunningTasks } from '@/runner/tasks.ts';
import { FusionPlugin, FusionVitePluginOptions, FusionVitePluginUnresolved, LoadedConfigTask } from '@/types';
import { forceArray } from '@/utilities/arr.ts';
import { copyFilesAndLog, linkFilesAndLog, moveFilesAndLog } from '@/utilities/fs.ts';
import { show } from '@/utilities/utilities.ts';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { Logger, mergeConfig, PluginOption, UserConfig } from 'vite';

const params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);

export let builder: ConfigBuilder;

const originalTasks = params._;
const extraVitePlugins: FusionPlugin[] = [];

export function useFusion(fusionOptions: FusionVitePluginUnresolved = {}, tasks?: string | string[]): PluginOption {
  let logger: Logger;

  const options = prepareFusionOptions(fusionOptions);

  if (tasks !== undefined || (Array.isArray(tasks) && tasks.length > 0)) {
    params._ = forceArray(tasks);
  } else {
    params._ = originalTasks;
  }

  if (options.cwd !== undefined) {
    params.cwd = options.cwd;
  }

  return [
    {
      name: 'fusion',
      configResolved(config) {
        logger = config.logger;

        config.plugins.push(...extraVitePlugins);

        for (const plugin of (config.plugins as FusionPlugin[])) {
          if ('buildConfig' in plugin) {
            plugin.buildConfig?.(builder);
          }
        }
      },
      async config(config, env) {
        let root: string;

        if (config.root) {
          root = resolve(config.root);
        } else {
          root = params.cwd || process.cwd();
        }

        delete config.root;
        // delete builder.config.root;

        process.chdir(root);

        builder = new ConfigBuilder(config, env, params);

        // Retrieve config file
        let tasks: Record<string, LoadedConfigTask>;

        if (typeof options.fusionfile === 'string' || !options.fusionfile) {
          params.config ??= options.fusionfile;
          const configFile = mustGetAvailableConfigFile(root, params);

          // Load config
          tasks = await loadConfigFile(configFile);
        } else if (typeof options.fusionfile === 'function') {
          tasks = expandModules(await options.fusionfile());
        } else {
          tasks = expandModules(options.fusionfile);
        }

        // Describe tasks
        if (params.list) {
          await displayAvailableTasks(tasks);
          return;
        }

        // Select running tasks
        const selectedTasks = selectRunningTasks([...params._] as string[], tasks);

        const runningTasks = (await resolveAllTasksAsProcessors(selectedTasks));

        for (const taskName in runningTasks) {
          const processors = runningTasks[taskName];

          for (const processor of processors) {
            await processor.config(taskName, builder);
          }
        }

        builder.merge(ConfigBuilder.globalOverrideConfig);
        builder.merge(builder.overrideConfig);

        // for (const plugin of plugins) {
        //   if (plugin.buildConfig) {
        //     await plugin.buildConfig(builder, env);
        //   }
        // }

        // console.log('plugin bottom', builder.config);
        //
        // show(builder.overrideConfig, 15)
        // show(builder.config, 15)

        return builder.config;
      },
      outputOptions(options) {
        const dir = options.dir!;
        const uploadDir = resolve(dir, 'upload');

        if (existsSync(uploadDir)) {
          throw new Error(
            `The output directory: "${dir}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
          );
        }
      },
    },
    {
      name: 'fusion:pre-handles',
      enforce: 'pre',
      async resolveId(source, importer, options) {
        for (const resolveId of builder.resolveIdCallbacks) {
          const result = await resolveId(source, importer, options);

          if (result) {
            return result;
          }
        }

        if (source.startsWith('hidden:')) {
          return source;
        }
      },
      async load(source, options) {
        for (const load of builder.loadCallbacks) {
          const result = await load(source, options);

          if (result) {
            return result;
          }
        }

        if (source.startsWith('hidden:')) {
          return '';
        }
      },
    },
    {
      name: 'fusion:post-handles',
      generateBundle(options, bundle) {
        for (const [fileName, chunk] of Object.entries(bundle)) {
          if (chunk.type === 'chunk' && chunk.facadeModuleId?.startsWith('hidden:')) {
            delete bundle[fileName];
          }
        }
      },
      async writeBundle(options, bundle) {
        // Todo: override logger to replace vite's files logs
        // @see https://github.com/windwalker-io/core/issues/1355
        await moveFilesAndLog(builder.moveTasks, options.dir ?? process.cwd(), logger);
        await copyFilesAndLog(builder.copyTasks, options.dir ?? process.cwd(), logger);
        await linkFilesAndLog(builder.linkTasks, options.dir ?? process.cwd(), logger);

        for (const callback of builder.postBuildCallbacks) {
          await callback();
        }

        for (const [name, task] of builder.tasks) {
          for (const callback of task.postCallbacks) {
            await callback();
          }
        }
      },
    },
  ];
}

function prepareFusionOptions(options: FusionVitePluginUnresolved): FusionVitePluginOptions {
  if (typeof options === 'string') {
    return {
      fusionfile: options,
    };
  }

  if (typeof options === 'function') {
    return {
      fusionfile: options,
    };
  }

  return options;
}

export function configureBuilder(handler: (builder: ConfigBuilder) => void) {
  handler(builder);
}

export function mergeViteConfig(config: UserConfig | null) {
  // if (config === null) {
  //   ConfigBuilder.globalOverrideConfig = {};
  //   return;
  // }
  //
  // ConfigBuilder.globalOverrideConfig = mergeConfig(ConfigBuilder.globalOverrideConfig, config);
  if (config === null) {
    builder.overrideConfig = {};
    return;
  }

  builder.overrideConfig = mergeConfig(ConfigBuilder.globalOverrideConfig, config);
}

export function outDir(outDir: string) {
  // ConfigBuilder.globalOverrideConfig = mergeConfig<UserConfig, UserConfig>(ConfigBuilder.globalOverrideConfig, {
  //   build: {
  //     outDir
  //   }
  // });
  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    build: {
      outDir
    }
  });
}

export function alias(src: string, dest: string) {
  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    resolve: {
      alias: {
        [src]: dest
      }
    }
  });
}

export function external(match: string, varName?: string) {
  const globals: Record<string, string> = {};

  if (varName) {
    globals[match] = varName;
  }

  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    build: {
      rollupOptions: {
        external: [match],
        output: {
          globals
        }
      }
    }
  });
}

export function plugin(...plugins: FusionPlugin[]) {
  extraVitePlugins.push(...plugins);
}

