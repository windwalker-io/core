import { MaybeArray } from 'rollup';
import { LoadedConfigTask, RunningTasks } from '../types/runner';
import { UserConfig } from 'vite';
export declare function selectRunningTasks(input: string[], tasks: Record<string, LoadedConfigTask>): Record<string, LoadedConfigTask>;
export declare function resolveAllTasksAsOptions(tasks: Record<string, LoadedConfigTask>): Promise<RunningTasks>;
export declare function resolveTaskAsFlat(name: string, task: LoadedConfigTask, cache: Record<string, MaybeArray<LoadedConfigTask>>): Promise<UserConfig[]>;
