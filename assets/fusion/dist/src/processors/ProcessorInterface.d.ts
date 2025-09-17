import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { MaybePromise } from 'rollup';
export type ProcessorPreview = {
    input: string;
    output: string;
    extra?: Record<string, any>;
};
export interface ProcessorInterface {
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
