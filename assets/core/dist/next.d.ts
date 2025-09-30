import { ConfigBuilder } from '@windwalker-io/fusion-next';
import { css } from '@windwalker-io/fusion-next';
import { FusionPlugin } from '@windwalker-io/fusion-next';
import { js } from '@windwalker-io/fusion-next';
import { MaybePromise } from '@windwalker-io/fusion-next';
import { OutputAsset } from 'rollup';
import { OutputChunk } from 'rollup';
import { PluginOption } from 'vite';
import { ProcessorInterface } from '@windwalker-io/fusion-next';
import { ProcessorPreview } from '@windwalker-io/fusion-next';

export declare function cloneAssets(patterns: Record<string, string>): {
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
};

export declare function containsMiddleGlob(str: string): boolean;

export declare function cssModulize(entry: string, dest: string): CssModulizeProcessor;

declare class CssModulizeProcessor implements ProcessorInterface {
    protected processor: ReturnType<typeof css>;
    protected bladePatterns: string[];
    protected cssPatterns: string[];
    constructor(processor: ReturnType<typeof css>, bladePatterns?: string[], cssPatterns?: string[]);
    parseBlades(...bladePatterns: (string[] | string)[]): this;
    mergeCss(...css: (string[] | string)[]): this;
    config(taskName: string, builder: ConfigBuilder): any;
    parseStylesFromBlades(files: string[]): string[];
    preview(): ProcessorPreview[];
}

export declare function ensureDirPath(path: string, slash?: '/' | '\\'): string;

export declare interface FindFileResult {
    fullpath: string;
    relativePath: string;
}

export declare function findFilesFromGlobArray(sources: string[]): FindFileResult[];

export declare function findModules(suffix?: string, rootModule?: string | null): string[];

export declare function findPackages(suffix?: string, withRoot?: boolean): string[];

export declare function globalAssets(options: WindwalkerAssetsOptions): FusionPlugin;

export declare function injectSystemJS(systemPath?: string, filter?: (file: OutputAsset | OutputChunk) => any): PluginOption;

export declare function installVendors(npmVendors?: string[], to?: string): {
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
};

export declare function jsModulize(entry: string, dest: string, options?: JsModulizeOptions): JsModulizeProcessor;

declare interface JsModulizeOptions {
    tmpPath?: string;
    cleanTmp?: boolean;
}

declare class JsModulizeProcessor implements ProcessorInterface {
    protected processor: ReturnType<typeof js>;
    protected options: JsModulizeOptions;
    protected scriptPatterns: string[];
    protected bladePatterns: string[];
    protected stagePrefix: string;
    constructor(processor: ReturnType<typeof js>, options?: JsModulizeOptions);
    config(taskName: string, builder: ConfigBuilder): any;
    /**
     * @see https://github.com/vitejs/vite/issues/6393#issuecomment-1006819717
     * @see https://stackoverflow.com/questions/76259677/vite-dev-server-throws-error-when-resolving-external-path-from-importmap
     */
    private ignoreMainImport;
    preview(): MaybePromise<ProcessorPreview[]>;
    mergeScripts(...patterns: (string | string[])[]): this;
    parseBlades(...bladePatterns: (string[] | string)[]): this;
    stage(stage: string): this;
}

export declare function loadJson(file: string): any;

export declare function removeLastGlob(str: string): string;

export declare function resolveModuleRealpath(url: string, module: string): string;

export declare function stripUrlQuery(src: string): string;

export declare function systemCSSFix(): PluginOption;

export declare function uniqId(prefix?: string, size?: number): string;

export declare interface WindwalkerAssetsOptions {
    clone?: Record<string, string>;
    reposition?: Record<string, string>;
}

export { }
