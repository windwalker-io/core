import chalk from 'chalk';
import archy, { type Data } from 'archy';
import { shuffle } from 'lodash-es';
import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';
import { LibraryOptions, UserConfig } from 'vite';
import { resolveTaskOptions } from './config';
import { LoadedConfigTask } from '../types/runner';

export async function displayAvailableTasks(tasks: Record<string, LoadedConfigTask>) {
  const keys = Object.keys(tasks);

  // Sort put default as first if exists
  keys.sort((a, b) => {
    if (a === 'default') {
      return -1;
    }

    if (b === 'default') {
      return 1;
    }

    return a.localeCompare(b);
  });

  const nodes: Array<Data | string> = [];

  for (const key of keys) {
    const task = tasks[key];
    const taskOptions = await resolveTaskOptions(task, true);

    nodes.push(await describeTasks(key, taskOptions));
  }

  const text = archy({
    label: chalk.magenta('Available Tasks'),
    nodes
  });

  console.log(text);
}

async function describeTasks(name: string, tasks: MaybeArray<UserConfig>): Promise<Data> {
  const nodes = [];
  // console.log(name, tasks)
  if (!Array.isArray(tasks)) {
    tasks = [tasks]
  }
  
  for (const task of tasks) {
    if (typeof task === 'function') {
      let taskOptions = await resolveTaskOptions(task, true);

      nodes.push(
        await describeTasks((task as Function).name, taskOptions)
      );
    } else {
      nodes.push(describeTaskDetail(task));
    }
  }

  return {
    label: chalk.cyan(name),
    nodes
  };
}

function describeTaskDetail(task: UserConfig, indent: number = 4): string {
  const str = [];

  const lib = task.build?.lib;

  // Input
  if (lib && lib.entry) {
    const entry = lib.entry;

    let inputStr = '';
    if (typeof entry === 'string') {
      inputStr = chalk.yellow(entry);
    } else if (Array.isArray(entry)) {
      inputStr = chalk.yellow(entry.join(', '));
    } else if (typeof entry === 'object') {
      inputStr = chalk.yellow(Object.values(entry).join(', '));
    }
    str.push(`Input: ${inputStr}`);
  }

  const output = task.build?.rollupOptions?.output;

  // Output
  if (output) {
    const outputs = Array.isArray(output) ? output : [output];
    outputs.forEach((output, index) => {
      let outStr = '';
      if (output.file) {
        outStr = chalk.green(output.file);
      } else if (output.dir) {
        outStr = chalk.green(output.dir);
      }
      str.push(`Output[${index}]: ${outStr}`);
    });
  }

  return str.join(" - ");
}

function countTask(task: MaybeArray<UserConfig>) {
  if (Array.isArray(task)) {
    return task.length;
  }

  return 1;
}
