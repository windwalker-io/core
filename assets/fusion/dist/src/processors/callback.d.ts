import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from './ProcessorInterface.ts';
import { MaybePromise } from 'rollup';
type CallbackHandler = (taskName: string, builder: ConfigBuilder) => MaybePromise<any>;
export declare function callback(handler: CallbackHandler): CallbackProcessor;
export declare function callbackAfterBuild(handler: CallbackHandler): CallbackProcessor;
declare class CallbackProcessor implements ProcessorInterface {
    protected handler: CallbackHandler;
    protected afterBuild: boolean;
    constructor(handler: CallbackHandler, afterBuild?: boolean);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
}
export {};
