import { TaskInput, TaskOutput } from '../types';
import { VueOptions } from '../types/vue';
import { UserConfig } from 'vite';
export declare function vue(input: TaskInput, output: TaskOutput, options?: VueOptions): Promise<UserConfig[]>;
