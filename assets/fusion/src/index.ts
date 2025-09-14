export * from '@/dep';
import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import * as fusion from '@/dep';
import { getArgsAfterDoubleDashes, parseArgv, runApp } from '@/runner/app';
import { findDefaultConfig, loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe.ts';
import { resolveAllTasksAsProcessors, selectRunningTasks } from '@/runner/tasks.ts';
import { FusionVitePluginOptions, LoadedConfigTask } from '@/types';
import { moveFilesAndLog } from '@/utilities/fs.ts';
import minimist from 'minimist';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { prepareParams, params as p } from '@/params';
import { mergeConfig, PluginOption, UserConfig } from 'vite';

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

export function useFusion(options: FusionVitePluginOptions = {}): PluginOption {
  let builder: ConfigBuilder;

  return {
    name: 'fusion',
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

      if (typeof options.fusionfile !== 'object') {
        params.config ??= options.fusionfile;
        const configFile = mustGetAvailableConfigFile(root, params);

        // Load config
        tasks = await loadConfigFile(configFile);
      } else {
        tasks = { ...options.fusionfile };
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

      console.log('plugin bottom', builder.config);

      return builder.config;
    },

    async writeBundle(options, bundle) {
      await moveFilesAndLog(builder.moveFilesMap, options.dir ?? process.cwd());
    }
  };
}

export function mergeViteConfig(config: UserConfig) {
  ConfigBuilder.defaultConfig = mergeConfig(ConfigBuilder.defaultConfig, config);
}

export function outDir(outDir: string) {
  ConfigBuilder.defaultConfig = mergeConfig<UserConfig, UserConfig>(ConfigBuilder.defaultConfig, {
    build: {
      outDir
    }
  });
}
