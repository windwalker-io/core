

import chalk from 'chalk';
import { resolve } from 'node:path';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';
import { buildAll, watchAll } from './runner/build';
import { loadConfigFile, mustGetAvailableConfigFile } from './runner/config';
import { displayAvailableTasks } from './runner/describe';
import { resolveAllTasksAsOptions, selectRunningTasks } from './runner/tasks';
import { RunnerCliParams } from './runner/types';
import { watch, rollup, defineConfig, RollupOptions } from 'rollup';

main();

async function main() {
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

  const argv: RunnerCliParams = app.parseSync(hideBin(process.argv));

  try {
    await run(argv);

    // Success exit
    // process.exit(0);
  } catch (e) {
    if (e instanceof Error) {
      if (argv.verbose && argv.verbose > 0) {
        throw e
      } else {
        console.error(e);
        process.exit(1);
      }
    } else {
      throw e;
    }
  }
}

async function run(params: RunnerCliParams) {
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

  const options = (await resolveAllTasksAsOptions(selectedTasks));

  if (params.watch) {
    await watchAll(defineConfig(options));
  } else {
    await buildAll(defineConfig(options));
  }
}
