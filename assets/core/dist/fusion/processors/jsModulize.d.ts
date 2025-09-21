import { ConfigBuilder, js, MaybePromise, ProcessorInterface, ProcessorPreview } from '@windwalker-io/fusion-next';
export declare function jsModulize(entry: string, dest: string): JsModulizeProcessor;
export declare class JsModulizeProcessor implements ProcessorInterface {
    protected processor: ReturnType<typeof js>;
    jsPatterns: string[];
    constructor(processor: ReturnType<typeof js>);
    config(taskName: string, builder: ConfigBuilder): undefined;
    preview(): MaybePromise<ProcessorPreview[]>;
    modules(...patterns: (string | string[])[]): this;
}
