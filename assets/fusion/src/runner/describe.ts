import chalk from 'chalk';
import archy, { type Data } from 'archy';
import { shuffle } from 'lodash-es';
import { MaybeArray, RollupOptions } from 'rollup';
import { resolveTaskOptions } from './config';
import { LoadedConfigTask } from './types';

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

async function describeTasks(name: string, tasks: MaybeArray<RollupOptions>): Promise<Data> {
  const nodes = [];
  // console.log(name, tasks)
  if (!Array.isArray(tasks)) {
    tasks = [tasks]
  }

  for (const task of tasks) {
    if (typeof task === 'function') {
      const taskOptions = await resolveTaskOptions(task, true);

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

function describeTaskDetail(task: RollupOptions, indent: number = 4): string {
  const str = [];

  // Input
  if (task.input) {
    let inputStr = '';
    if (typeof task.input === 'string') {
      inputStr = chalk.yellow(task.input);
    } else if (Array.isArray(task.input)) {
      inputStr = chalk.yellow(task.input.join(', '));
    } else if (typeof task.input === 'object') {
      inputStr = chalk.yellow(Object.values(task.input).join(', '));
    }
    str.push(`Input: ${inputStr}`);
  }

  // Output
  if (task.output) {
    const outputs = Array.isArray(task.output) ? task.output : [task.output];
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

function countTask(task: MaybeArray<RollupOptions>) {
  if (Array.isArray(task)) {
    return task.length;
  }

  return 1;
}
