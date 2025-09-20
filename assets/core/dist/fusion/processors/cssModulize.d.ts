import { ConfigBuilder, css, ProcessorInterface, ProcessorPreview } from '@windwalker-io/fusion-next';
export declare function cssModulize(entry: string, dest: string): CssModulizeProcessor;
declare class CssModulizeProcessor implements ProcessorInterface {
    protected processor: ReturnType<typeof css>;
    protected bladePatterns: string[];
    protected cssPatterns: string[];
    constructor(processor: ReturnType<typeof css>, bladePatterns?: string[], cssPatterns?: string[]);
    parseBlades(...bladePatterns: (string[] | string)[]): this;
    mergeCss(...css: (string[] | string)[]): this;
    config(taskName: string, builder: ConfigBuilder): undefined;
    preview(): ProcessorPreview[];
}
export {};
