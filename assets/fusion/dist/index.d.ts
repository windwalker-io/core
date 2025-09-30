import { Arguments } from 'yargs';
import { ConfigEnv } from 'vite';
import { default as default_2 } from 'crypto';
import { NormalizedOutputOptions } from 'rollup';
import { OutputBundle } from 'rollup';
import { Plugin as Plugin_2 } from 'vite';
import { PluginOption } from 'vite';
import { PreRenderedAsset } from 'rollup';
import { PreRenderedChunk } from 'rollup';
import { UserConfig } from 'vite';
import { ViteDevServer } from 'vite';

export declare function alias(src: string, dest: string): void;

export declare let builder: ConfigBuilder;

export declare class BuildTask {
    input: string;
    group?: string | undefined;
    id: string;
    output?: string | ((chunkInfo: PreRenderedChunk) => any);
    postCallbacks: ((options: NormalizedOutputOptions, bundle: OutputBundle) => MaybePromise<any>)[];
    constructor(input: string, group?: string | undefined);
    dest(output?: string | ((chunkInfo: PreRenderedChunk) => any)): this;
    addPostCallback(callback: () => void): this;
    normalizeOutput(output: string, ext?: string): string;
    static toFileId(input: string, group?: string): string;
}

export declare function callback(handler: CallbackHandler): CallbackProcessor;

export declare function callbackAfterBuild(handler: CallbackHandler): CallbackProcessor;

declare type CallbackHandler = (taskName: string, builder: ConfigBuilder) => MaybePromise<any>;

declare class CallbackProcessor implements ProcessorInterface {
    constructor(
    /** @internal */
    handler: CallbackHandler, 
    /** @internal */
    afterBuild?: boolean);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare function chunkDir(dir: string): void;

export declare function clean(...paths: string[]): void;

export declare class ConfigBuilder {
    config: UserConfig;
    env: ConfigEnv;
    fusionOptions: FusionPluginOptions;
    server: ViteDevServer | null;
    static globalOverrideConfig: UserConfig;
    overrideConfig: UserConfig;
    entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[];
    chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[];
    assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string | undefined | void)[];
    moveTasks: FileTasks;
    copyTasks: FileTasks;
    linkTasks: FileTasks<'link'>;
    postBuildCallbacks: ((options: NormalizedOutputOptions, bundle: OutputBundle) => MaybePromise<void>)[];
    resolveIdCallbacks: Exclude<Plugin_2['resolveId'], undefined>[];
    loadCallbacks: Exclude<Plugin_2['load'], undefined>[];
    watches: WatchTask[];
    cleans: string[];
    tasks: Map<string, BuildTask>;
    constructor(config: UserConfig, env: ConfigEnv, fusionOptions: FusionPluginOptions);
    merge(override: UserConfig | ((config: UserConfig) => UserConfig)): this;
    private getDefaultOutput;
    private getChunkDir;
    private getChunkNameFromTask;
    ensurePath(path: string, def?: any): this;
    get(path: string): any;
    set(path: string, value: any): this;
    addTask(input: string, group?: string): BuildTask;
    addCleans(...paths: string[]): this;
    relativePath(to: string): string;
    debug(): void;
}

export declare function configureBuilder(handler: (builder: ConfigBuilder) => void): void;

export declare function copy(input: TaskInput, dest: string): CopyProcessor;

export declare function copyGlob(src: string, dest: string): Promise<void>;

declare class CopyProcessor implements ProcessorInterface {
    input: TaskInput;
    dest: string;
    constructor(input: TaskInput, dest: string);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare function css(input: TaskInput, output?: TaskOutput, options?: CssOptions): CssProcessor;

declare type CssOptions = ProcessorOptions & {
    clean?: boolean;
    rebase?: boolean;
};

declare class CssProcessor implements ProcessorInterface {
    protected input: TaskInput;
    protected output?: TaskOutput | undefined;
    protected options: CssOptions;
    constructor(input: TaskInput, output?: TaskOutput | undefined, options?: CssOptions);
    config(taskName: string, builder: ConfigBuilder): BuildTask[];
    preview(): MaybePromise<ProcessorPreview[]>;
}

declare const _default: {
    useFusion: typeof useFusion;
    configureBuilder: typeof configureBuilder;
    overrideViteConfig: typeof overrideViteConfig;
    overrideOptions: typeof overrideOptions;
    outDir: typeof outDir;
    chunkDir: typeof chunkDir;
    alias: typeof alias;
    externals: typeof externals;
    plugin: typeof plugin;
    clean: typeof clean;
    fullReloads: typeof fullReloads;
    params: RunnerCliParams;
    isVerbose: boolean;
    isDev: boolean;
    isProd: boolean;
    isWindows: typeof fusion.isWindows;
    shortHash: typeof fusion.shortHash;
    copyGlob: typeof fusion.copyGlob;
    moveGlob: typeof fusion.moveGlob;
    symlink: typeof fusion.symlink;
    fileToId: typeof fusion.fileToId;
    getGlobBaseFromPattern: typeof fusion.getGlobBaseFromPattern;
    css: typeof fusion.css;
    js: typeof fusion.js;
    move: typeof fusion.move;
    copy: typeof fusion.copy;
    link: typeof fusion.link;
    callback: typeof fusion.callback;
    callbackAfterBuild: typeof fusion.callbackAfterBuild;
};
export default _default;

export declare function externals(...externals: (string | RegExp)[]): void;

declare type ExtraViteOptions = OverrideOptions<ConfigBuilder>;

declare type FileTask<T extends keyof FileTaskOptionTypes = 'none'> = {
    src: string;
    dest: string;
    options: FileTaskOptionTypes[T];
};

declare type FileTaskOptionTypes = {
    'none': any;
    'move': any;
    'copy': any;
    'link': LinkOptions;
};

declare type FileTasks<T extends keyof FileTaskOptionTypes = 'none'> = FileTask<T>[];

export declare function fileToId(input: string, group?: string): string;

export declare function fullReloads(...paths: string[]): void;

declare namespace fusion {
    export {
        isVerbose,
        isDev,
        isProd,
        params,
        isWindows,
        shortHash,
        copyGlob,
        moveGlob,
        symlink,
        fileToId,
        getGlobBaseFromPattern,
        FusionPlugin,
        MaybePromise,
        MaybeArray,
        BuildTask,
        ConfigBuilder,
        css,
        js,
        move,
        copy,
        link,
        callback,
        callbackAfterBuild,
        ProcessorPreview,
        ProcessorInterface
    }
}

declare type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);

export declare type FusionPlugin = PluginOption & {
    buildConfig?: (builder: ConfigBuilder) => MaybePromise<any>;
};

declare interface FusionPluginOptions {
    fusionfile?: string | Fusionfile;
    chunkDir?: string;
    chunkNameObfuscation?: boolean;
    plugins?: FusionPlugin[];
    cliParams?: RunnerCliParams;
}

declare type FusionPluginOptionsUnresolved = FusionPluginOptions | string | (() => MaybePromise<Record<string, any>>);

export declare function getGlobBaseFromPattern(pattern: string): string;

export declare const isDev: boolean;

export declare const isProd: boolean;

export declare let isVerbose: boolean;

export declare function isWindows(): boolean;

export declare function js(input: TaskInput, output?: TaskOutput): ProcessorInterface;

export declare function link(input: TaskInput, dest: string, options?: LinkOptions): LinkProcessor;

declare interface LinkOptions {
    force?: boolean;
}

declare class LinkProcessor implements ProcessorInterface {
    input: TaskInput;
    dest: string;
    options: LinkOptions;
    constructor(input: TaskInput, dest: string, options?: LinkOptions);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare type MaybeArray<T> = T | T[];

export declare type MaybePromise<T> = T | Promise<T>;

export declare function move(input: TaskInput, dest: string): MoveProcessor;

export declare function moveGlob(src: string, dest: string): Promise<void>;

declare class MoveProcessor implements ProcessorInterface {
    input: TaskInput;
    dest: string;
    constructor(input: TaskInput, dest: string);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare function outDir(outDir: string): void;

declare type OverrideOptions<T> = Partial<T> | ((options: Partial<T>) => T | undefined);

export declare function overrideOptions(options: FusionPluginOptions): FusionPluginOptions;

export declare function overrideViteConfig(config: UserConfig | null): void;

export declare let params: RunnerCliParams | undefined;

export declare function plugin(...plugins: FusionPlugin[]): void;

export declare interface ProcessorInterface {
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

declare interface ProcessorOptions {
    vite?: ExtraViteOptions;
    verbose?: boolean;
}

export declare type ProcessorPreview = {
    input: string;
    output: string;
    extra?: Record<string, any>;
};

declare type RunnerCliOptions = {
    cwd?: string;
    l?: boolean;
    list?: boolean;
    c?: string;
    config?: string;
    v?: number;
    verbose?: number;
    serverFile?: string;
    s?: string;
};

declare type RunnerCliParams = Arguments<RunnerCliOptions>;

export declare function shortHash(bufferOrString: default_2.BinaryLike, short?: number | null): string;

export declare function symlink(target: string, link: string, force?: boolean): Promise<void>;

declare type TaskInput = string | string[];

declare type TaskOutput = string;

export declare function useFusion(fusionOptions?: FusionPluginOptionsUnresolved, tasks?: string | string[]): PluginOption;

declare type WatchTask = string | {
    file: string;
    moduleFile: string;
    updateType: 'js-update' | 'css-update' | 'full-reload';
};

export { }
