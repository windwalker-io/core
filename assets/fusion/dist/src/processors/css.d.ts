import { CssOptions, TaskInput, TaskOutput } from '../types';
import { MaybeArray } from 'rollup';
import { UserConfig } from 'vite';
export declare function css(input: TaskInput, output: TaskOutput, options?: CssOptions): Promise<MaybeArray<UserConfig>>;
