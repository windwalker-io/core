import { ProcessorInterface, ProcessorPreview } from '@/processors/ProcessorInterface.ts';
import { LoadedConfigTask } from '@/types';
import { forceArray } from '@/utilities/arr.ts';
import archy, { type Data } from 'archy';
import chalk from 'chalk';
import { MaybeArray } from 'rollup';
import { UserConfig } from 'vite';
import { resolveTaskOptions } from './config';

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
    // const taskOptions = await resolveTaskOptions(task, true);

    nodes.push(await describeTasks(key, task));
  }

  const text = archy({
    label: chalk.magenta('Available Tasks'),
    nodes
  });

  console.log(text);
}

async function describeTasks(name: string, tasks: LoadedConfigTask): Promise<Data> {
  const nodes = [];
  tasks = forceArray(await tasks);

  for (let task of tasks) {
    const processors = await resolveTaskOptions(task, true);

    for (const processor of processors) {
      if (typeof processor === 'function') {
        nodes.push(
          await describeTasks((processor as Function).name, processor)
        );
      } else {
        nodes.push(...await describeProcessor(processor));
      }
    }
  }

  return {
    label: chalk.cyan(name),
    nodes
  };
}

async function describeProcessor(processor: ProcessorInterface): Promise<string[]> {
  const results = await processor.preview();

  return Promise.all(results.map((result) => describeProcessorPreview(result)));
}

async function describeProcessorPreview(preview: ProcessorPreview): Promise<string> {
  const str = [];

  const { input: entry, output, extra } = preview;

  // Input
  const inputStr = chalk.yellow(entry);
  // if (typeof entry === 'string') {
  //   inputStr = chalk.yellow(entry);
  // } else if (Array.isArray(entry)) {
  //   inputStr = chalk.yellow(entry.join(', '));
  // } else if (typeof entry === 'object') {
  //   inputStr = chalk.yellow(Object.values(entry).join(', '));
  // }
  str.push(`Input: ${inputStr}`);

  // Output
  // if (output) {
  //   const outputs = Array.isArray(output) ? output : [output];
  //   outputs.forEach((output, index) => {
  //     let outStr = '';
  //     if (output.file) {
  //       outStr = chalk.green(output.file);
  //     } else if (output.dir) {
  //       outStr = chalk.green(output.dir);
  //     }
  //     str.push(`Output[${index}]: ${outStr}`);
  //   });
  // }

  const outStr = chalk.green(output);
  str.push(`Output: ${outStr}`);

  return str.join(" - ");
}

function countTask(task: MaybeArray<UserConfig>) {
  if (Array.isArray(task)) {
    return task.length;
  }

  return 1;
}
