import { cloneAssets } from './cloneAssets';
import { ConfigBuilder } from '@windwalker-io/fusion-next';
import { css } from '@windwalker-io/fusion-next';
import { FusionPlugin } from '@windwalker-io/fusion-next';
import { installVendors } from './installVendors';
import { ProcessorInterface } from '@windwalker-io/fusion-next';
import { ProcessorPreview } from '@windwalker-io/fusion-next';

export { cloneAssets }

export declare function containsMiddleGlob(str: string): boolean;

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

export declare function ensureDirPath(path: string, slash?: '/' | '\\'): string;

export declare function findModules(suffix?: string): string[];

export { installVendors }

export declare function loadJson(file: string): any;

export declare function removeLastGlob(str: string): string;

export declare function uniqId(prefix?: string, size?: number): string;

export declare function windwalkerAssets(options: WindwalkerAssetsOptions): FusionPlugin;

export declare interface WindwalkerAssetsOptions {
    clone?: Record<string, string>;
    reposition?: Record<string, string>;
}

export { }
