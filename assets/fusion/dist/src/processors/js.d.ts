import { default as BuildTask } from '../builder/BuildTask.ts';
import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface.ts';
import { TaskInput, TaskOutput } from '../types';
import { MaybePromise } from 'rollup';
export declare function js(input: TaskInput, output?: TaskOutput): ProcessorInterface;
export declare class JsProcessor implements ProcessorInterface {
    input: TaskInput;
    output?: TaskOutput | undefined;
    constructor(input: TaskInput, output?: TaskOutput | undefined);
    config(taskName: string, builder: ConfigBuilder): BuildTask[];
    preview(): MaybePromise<ProcessorPreview[]>;
}
