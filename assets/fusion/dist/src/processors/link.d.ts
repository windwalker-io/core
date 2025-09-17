import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface.ts';
import { LinkOptions, TaskInput } from '../types';
import { MaybePromise } from 'rollup';
export declare function link(input: TaskInput, dest: string, options?: LinkOptions): LinkProcessor;
export declare class LinkProcessor implements ProcessorInterface {
    input: TaskInput;
    dest: string;
    options: LinkOptions;
    constructor(input: TaskInput, dest: string, options?: LinkOptions);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
