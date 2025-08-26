import { RunnerCliParams, RunningTasks } from '../types';
export declare function buildAll(runningTasks: RunningTasks, params: RunnerCliParams): Promise<void>;
export declare function watchAll(runningTasks: RunningTasks, params: RunnerCliParams): Promise<void>;
