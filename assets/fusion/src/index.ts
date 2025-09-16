export * from '@/dep';
import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import * as fusion from '@/dep';
import clean from '@/plugins/clean.ts';
import { getArgsAfterDoubleDashes, parseArgv, runApp } from '@/runner/app';
import { expandModules, findDefaultConfig, loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe.ts';
import { resolveAllTasksAsProcessors, selectRunningTasks } from '@/runner/tasks.ts';
import { FusionVitePluginUnresolved, FusionVitePluginOptions, LoadedConfigTask } from '@/types';
import { forceArray } from '@/utilities/arr.ts';
import { moveFilesAndLog } from '@/utilities/fs.ts';
import { show } from '@/utilities/utilities.ts';
import minimist from 'minimist';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { prepareParams, params as p } from '@/params';
import { Logger, mergeConfig, PluginOption, UserConfig } from 'vite';
import swc from '@vitejs/plugin-react-swc';

export default fusion;
//
// const isCliRunning = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];
//
// if (isCliRunning) {
//   const params = prepareParams(parseArgv());
//
//   runApp(params!);
// }

const params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);

export let builder: ConfigBuilder;

const originalTasks = params._;

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

        // console.log('plugin bottom', builder.config);
        //
        // show(builder.overrideConfig, 15)
        show(builder.config, 15)

        return builder.config;
      },

      closeBundle(error) {
        //
      },
    },
    {
      name: 'fusion:file-handles',
      async writeBundle(options, bundle) {
        // Todo: override logger to replace vite's files logs
        // @see https://github.com/windwalker-io/core/issues/1355
        await moveFilesAndLog(builder.moveFilesMap, options.dir ?? process.cwd(), logger);

        for (const callback of builder.postBuildCallbacks) {
          await callback();
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

