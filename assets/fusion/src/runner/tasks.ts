import { ProcessorInterface } from '../processors/ProcessorInterface.ts';
import chalk from 'chalk';
import { uniq } from 'lodash-es';
import { MaybeArray } from 'rollup';
import { resolveTaskOptions } from '../runner/config';
import { LoadedConfigTask, RunningTasks } from '../types/runner.ts';
import { UserConfig } from 'vite';

export function selectRunningTasks(
  input: string[],
  tasks: Record<string, LoadedConfigTask>
): Record<string, LoadedConfigTask> {
  input = uniq(input);

  if (input.length === 0) {
    input.push('default');
  }

  const selected: Record<string, LoadedConfigTask> = {};

  for (const name of input) {
    if (tasks[name]) {
      selected[name] = tasks[name];
    } else {
      throw new Error(`Task "${chalk.cyan(name)}" not found in fusion config.`);
    }
  }

  return selected;
}

export async function resolveAllTasksAsProcessors(tasks: Record<string, LoadedConfigTask>): Promise<RunningTasks> {
  const cache: Record<string, MaybeArray<LoadedConfigTask>> = {};
  const allTasks: RunningTasks = {};

  for (const name in tasks) {
    const task = tasks[name];

    allTasks[name] = (await resolveTaskAsFlat(name, task, cache));
  }

  return allTasks;
}

export async function resolveTaskAsFlat(
  name: string,
  task: LoadedConfigTask,
  cache: Record<string, MaybeArray<LoadedConfigTask>>
): Promise<ProcessorInterface[]> {
  const results: ProcessorInterface[] = [];

  if (Array.isArray(task)) {
    for (const n in task) {
      const t = task[n];
      results.push(...await resolveTaskAsFlat(n, t, cache));
    }
  } else if (typeof task === 'function') {
    name = task.name || name;

    if (cache[name]) {
      return [];
    }

    cache[name] = task;

    const resolved = await resolveTaskOptions(task, true);

    if (Array.isArray(resolved)) {
      for (const n in resolved) {
        const t = resolved[n];
        results.push(...await resolveTaskAsFlat(n, t, cache));
      }
    }
  } else {
    results.push(await task);
  }

  return results;
}
