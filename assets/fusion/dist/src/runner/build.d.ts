import { RunningTasks } from '../types';
import { UserConfig } from 'vite';
export declare function buildAll(runningTasks: RunningTasks): Promise<void>;
export declare function watchAll(optionsList: UserConfig[]): Promise<void>;
