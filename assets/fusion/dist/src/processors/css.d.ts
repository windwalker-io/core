import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface';
import { CssOptions, TaskInput, TaskOutput } from '../types';
import { MaybePromise } from 'rollup';
export declare function css(input: TaskInput, output?: TaskOutput, options?: CssOptions): CssProcessor;
export declare class CssProcessor implements ProcessorInterface {
    protected input: TaskInput;
    protected output?: TaskOutput | undefined;
    protected options: CssOptions;
    constructor(input: TaskInput, output?: TaskOutput | undefined, options?: CssOptions);
    config(taskName: string, builder: ConfigBuilder): Promise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
