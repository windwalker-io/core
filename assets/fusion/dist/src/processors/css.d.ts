import { default as ConfigBuilder } from '../ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface';
import { CssOptions, TaskInput, TaskOutput } from '../types';
import { MaybeArray, MaybePromise } from 'rollup';
import { UserConfig } from 'vite';
export declare function css(input: TaskInput, output: TaskOutput, options?: CssOptions): CssProcessor;
export declare class CssProcessor implements ProcessorInterface {
    protected input: TaskInput;
    protected output: TaskOutput;
    protected options: CssOptions;
    constructor(input: TaskInput, output: TaskOutput, options?: CssOptions);
    config(taskName: string, builder: ConfigBuilder): Promise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
export declare function cssBak(input: TaskInput, output: TaskOutput, options?: CssOptions): Promise<MaybeArray<UserConfig>>;
