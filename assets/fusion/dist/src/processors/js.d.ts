import { JsOptions, TaskInput, TaskOutput } from '../types';
import { UserConfig } from 'vite';
export declare function js(input: TaskInput, output: TaskOutput, options?: JsOptions): Promise<UserConfig[]>;
