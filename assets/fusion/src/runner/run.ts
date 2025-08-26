import { buildAll, watchAll } from '@/runner/build';
import { loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe';
import { resolveAllTasksAsOptions, selectRunningTasks } from '@/runner/tasks';
import { RunnerCliParams } from '@/types/runner';
import { defineAllConfigs } from '@/utilities/vite';
import { resolve } from 'node:path';
import { inspect } from 'node:util';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';

export function parseArgv(): RunnerCliParams {
  const app = yargs();

  app.option('watch', {
    alias: 'w',
    type: 'boolean',
    description: 'Watch files for changes and re-run the tasks',
  });

  app.option('cwd', {
    type: 'string',
    description: 'Current working directory',
  });

  app.option('list', {
    alias: 'l',
    type: 'boolean',
    description: 'List all available tasks',
  });

  app.option('config', {
    alias: 'c',
    type: 'string',
    description: 'Path to config file',
  });

  app.option('verbose', {
    alias: 'v',
    type: 'count',
    description: 'Increase verbosity of output. Use multiple times for more verbosity.',
  });

  return app.parseSync(hideBin(process.argv));
}

export async function run(argv: RunnerCliParams) {
  try {
    await processApp(argv);

    // Success exit
    // process.exit(0);
  } catch (e) {
    if (e instanceof Error) {
      if (argv.verbose && argv.verbose > 0) {
        throw e;
      } else {
        console.error(e);
        process.exit(1);
      }
    } else {
      throw e;
    }
  }
}

export async function processApp(params: RunnerCliParams) {
  let cwd = params?.cwd;
  let root: string;

  if (cwd) {
    root = cwd = resolve(cwd);
    process.chdir(cwd);
  } else {
    root = process.cwd();
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

  const runningTasks = (await resolveAllTasksAsOptions(selectedTasks));

  // console.log(inspect(runningTasks, { depth: null, colors: true }));

  //
  // if (params.watch) {
  //   await watchAll(defineAllConfigs(options));
  // } else {
    await buildAll(runningTasks);
  // }
}
