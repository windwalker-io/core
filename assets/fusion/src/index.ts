export * from '@/dep';
import ConfigBuilder from '@/ConfigBuilder';
import * as fusion from '@/dep';
import { getArgsAfterDoubleDashes, parseArgv, runApp } from '@/runner/app';
import { findDefaultConfig, loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe.ts';
import { resolveAllTasksAsProcessors, selectRunningTasks } from '@/runner/tasks.ts';
import { FusionVitePluginOptions } from '@/types';
import minimist from 'minimist';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { prepareParams, params as p } from '@/params';
import { PluginOption } from 'vite';

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
  return {
    name: 'fusion',
    async config(config, env) {
      const builder = new ConfigBuilder(config, env);

      let cwd = params?.cwd;
      let root: string;

      if (config.root) {
        root = resolve(config.root);
      } else {
        root = params.cwd || process.cwd();
      }

      // Retrieve config file
      const configFile = mustGetAvailableConfigFile(root, params);

      // Load config
      const tasks = await loadConfigFile(configFile);

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

      console.log(builder.config);
    }
  };
}


