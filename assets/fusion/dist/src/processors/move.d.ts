import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface.ts';
import { TaskInput } from '../types';
import { MaybePromise } from 'rollup';
export declare function move(input: TaskInput, dest: string): MoveProcessor;
export declare class MoveProcessor implements ProcessorInterface {
    input: TaskInput;
    dest: string;
    constructor(input: TaskInput, dest: string);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
