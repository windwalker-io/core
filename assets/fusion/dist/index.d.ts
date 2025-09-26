import { Arguments } from 'yargs';
import { ConfigEnv } from 'vite';
import { default as Crypto_2 } from 'crypto';
import { default as default_2 } from 'crypto';
import { NormalizedOutputOptions } from 'rollup';
import { OutputBundle } from 'rollup';
import { Plugin as Plugin_2 } from 'vite';
import { PluginOption } from 'vite';
import { PreRenderedAsset } from 'rollup';
import { PreRenderedChunk } from 'rollup';
import { RollupOptions } from 'rollup';
import { UserConfig } from 'vite';

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

declare class BuildTask_2 {
    id: string;
    output?: string | ((chunkInfo: PreRenderedChunk) => any);
    postCallbacks: ((options: NormalizedOutputOptions, bundle: OutputBundle) => MaybePromise_2<any>)[] = [];

    constructor(public input: string, public group?: string) {
        this.id = BuildTask.toFileId(input, group);

        this.input = normalize(input);
    }

    dest(output?: string | ((chunkInfo: PreRenderedChunk) => any)) {
        if (typeof output === 'string') {
            output = this.normalizeOutput(output);
        }

        this.output = output;

        return this;
    }

    addPostCallback(callback: () => void) {
        this.postCallbacks.push(callback);
        return this;
    }

    normalizeOutput(output: string, ext = '.js') {
        if (output.endsWith('/') || output.endsWith('\\')) {
            output += parse(this.input).name + ext;
        }

        // if (output.startsWith('.')) {
        //   output = resolve(output);
        // }

        return output;
    }

    static toFileId(input: string, group?: string) {
        return fileToId(input, group);
    }
}

export declare function callback(handler: CallbackHandler_2): CallbackProcessor;

declare function callback_2(handler: CallbackHandler) {
    return new CallbackProcessor(handler);
}

export declare function callbackAfterBuild(handler: CallbackHandler_2): CallbackProcessor;

declare function callbackAfterBuild_2(handler: CallbackHandler) {
    return new CallbackProcessor(handler, true);
}

declare type CallbackHandler = (taskName: string, builder: ConfigBuilder_2) => MaybePromise_2<any>;

declare type CallbackHandler_2 = (taskName: string, builder: ConfigBuilder) => MaybePromise<any>;

declare class CallbackProcessor implements ProcessorInterface {
    constructor(
    /** @internal */
    handler: CallbackHandler_2, 
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
    watches: string[];
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

declare class ConfigBuilder_2 {
    static globalOverrideConfig: UserConfig = {};
    overrideConfig: UserConfig = {};

    entryFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[] = [];
    chunkFileNamesCallbacks: ((chunkInfo: PreRenderedChunk) => string | undefined | void)[] = [];
    assetFileNamesCallbacks: ((chunkInfo: PreRenderedAsset) => string | undefined | void)[] = [];

    moveTasks: FileTasks_2 = [];
    copyTasks: FileTasks_2 = [];
    linkTasks: FileTasks_2<'link'> = [];
    postBuildCallbacks: ((options: NormalizedOutputOptions, bundle: OutputBundle) => MaybePromise_2<void>)[] = [];
    resolveIdCallbacks: Exclude<Plugin_2['resolveId'], undefined>[] = [];
    loadCallbacks: Exclude<Plugin_2['load'], undefined>[] = [];
    // fileNameMap: Record<string, string> = {};

    // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
    watches: string[] = [];
    cleans: string[] = [];

    tasks: Map<string, BuildTask_2> = new Map();

    constructor(public config: UserConfig, public env: ConfigEnv, public fusionOptions: FusionPluginOptions_2) {
        // this.ensurePath('build', {});
        // this.ensurePath('build.rollupOptions', {
        //   input: {},
        //   output: this.getDefaultOutput(),
        // });
        // this.ensurePath('plugins', []);

        this.config = mergeConfig<UserConfig, UserConfig>(
            {
            build: {
                manifest: 'manifest.json',
                rollupOptions: {
                    preserveEntrySignatures: 'strict',
                    input: {},
                    output: this.getDefaultOutput(),
                    // external: (source: string, importer: string | undefined, isResolved: boolean) => {
                    //   for (const external of this.externals) {
                    //     const result = external(source, importer, isResolved);
                    //
                    //     if (result) {
                    //       return true;
                    //     }
                    //   }
                    // },
                },
                emptyOutDir: false,
                sourcemap: env.mode !== 'production' ? 'inline' : false,
            },
            plugins: [],
            css: {
                devSourcemap: true,
            },
            esbuild: {
                // Todo: Remove if esbuild supports decorators by default
                target: 'es2022',
            }
        },
        this.config
        );

        this.addTask('hidden:placeholder');
    }

    merge(override: UserConfig | ((config: UserConfig) => UserConfig)) {
        if (typeof override === 'function') {
            this.config = override(this.config) ?? this.config;

            return this;
        }

        this.config = mergeConfig(this.config, override);

        return this;
    }

    private getDefaultOutput(): RollupOptions['output'] {
        let serial = 0;

        return {
            entryFileNames: (chunkInfo) => {
                const name = this.getChunkNameFromTask(chunkInfo);

                if (name) {
                    return name;
                }

                for (const entryFileNamesCallback of this.entryFileNamesCallbacks) {
                    const name = entryFileNamesCallback(chunkInfo);

                    if (name) {
                        return name;
                    }
                }

                // console.log(chunkInfo, this.relativePath(chunkInfo.facadeModuleId));

                return '[name].js';
            },
            chunkFileNames: (chunkInfo) => {
                serial++;
                const name = this.getChunkNameFromTask(chunkInfo);

                if (name) {
                    return name;
                }

                for (const chunkFileNamesCallback of this.chunkFileNamesCallbacks) {
                    const name = chunkFileNamesCallback(chunkInfo);

                    if (name) {
                        return name;
                    }
                }

                const chunkDir = this.getChunkDir();

                if (this.env.mode === 'production' && this.fusionOptions.chunkNameObfuscation) {
                    return `${chunkDir}${serial}.js`;
                }

                return `${chunkDir}[name]-[hash].js`;
            },
            assetFileNames: (assetInfo) => {
                // if (this.fileNameMap[assetInfo.name]) {
                //   assetInfo.name = this.fileNameMap[assetInfo.name];
                //   return assetInfo.name;
                // }

                for (const assetFileNamesCallback of this.assetFileNamesCallbacks) {
                    const name = assetFileNamesCallback(assetInfo);

                    if (name) {
                        return name;
                    }
                }

                return '[name].[ext]';
            }
        };
    }

    private getChunkDir(): string {
        let chunkDir = this.fusionOptions.chunkDir ?? 'chunks';
        chunkDir.replace(/\\/g, '/');

        // Ensure trailing slash
        if (chunkDir && !chunkDir.endsWith('/')) {
            chunkDir += '/';
        }

        if (chunkDir === './' || chunkDir === '/') {
            chunkDir = '';
        }

        return chunkDir;
    }

    private getChunkNameFromTask(chunkInfo: PreRenderedChunk) {
        if (this.tasks.has(chunkInfo.name)) {
            const output = this.tasks.get(chunkInfo.name)?.output;

            if (output) {
                const name = typeof output === 'function' ? output(chunkInfo) : output;

                if (!isAbsolute(name)) {
                    return name;
                }
            }
        }

        return undefined;
    }

    ensurePath(path: string, def: any = {}) {
        if (get(this.config, path) == null) {
            set(this.config, path, def);
        }

        return this;
    }

    get(path: string) {
        return get(this.config, path);
    }

    set(path: string, value: any) {
        set(this.config, path, value);
        return this;
    }

    addTask(input: string, group?: string) {
        const task = new BuildTask(input, group);

        this.tasks.set(task.id, task);

        const inputOptions = this.config.build!.rollupOptions!.input! as Record<string, string>;
        inputOptions[task.id] = task.input;

        return task;
    }

    addCleans(...paths: string[]) {
        this.cleans.push(...paths);

        return this;
    }

    // addExternals(externals: Externalize) {
    //   if (Array.isArray(externals)) {
    //     this.externals.push((rollupOptions) => {
    //       rollupOptions.external
    //     })
    //   } else if (typeof externals === 'object') {
    //
    //   } else {
    //
    //   }
    // }

    // addPlugin(plugin: PluginOption) {
    //   this.config.plugins?.push(plugin);
    // }
    //
    // removePlugin(plugin: string | PluginOption) {
    //   this.config.plugins = this.config.plugins?.filter((p) => {
    //     if (!p) {
    //       return true;
    //     }
    //
    //     if (typeof plugin === 'string' && typeof p === 'object' && 'name' in p) {
    //       return p.name !== plugin;
    //     } else if (typeof plugin === 'object' && typeof p === 'object') {
    //       return p !== plugin;
    //     }
    //
    //     return true;
    //   });
    // }

    relativePath(to: string) {
        return relative(process.cwd(), to);
    }

    debug() {
        show(this.config);
    }
}

export declare function configureBuilder(handler: (builder: ConfigBuilder) => void): void;

export declare function copy(input: TaskInput_2, dest: string): CopyProcessor;

declare function copy_2(input: TaskInput, dest: string) {
    return new CopyProcessor(input, dest);
}

export declare function copyGlob(src: string, dest: string): Promise<void>;

declare async function copyGlob_2(src: string, dest: string): Promise<void> {
    const promises = handleFilesOperation(
    src,
    dest,
        {
        outDir: process.cwd(),
        handler: async (src, dest) => fs.copy(src, dest, { overwrite: true }),
        globOptions: { onlyFiles: true }
    }
    );

    await Promise.all(promises);
}

declare class CopyProcessor implements ProcessorInterface {
    input: TaskInput_2;
    dest: string;
    constructor(input: TaskInput_2, dest: string);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare function css(input: TaskInput_2, output?: TaskOutput_2, options?: CssOptions_2): CssProcessor_2;

declare function css_2(
input: TaskInput,
output?: TaskOutput,
options: CssOptions = {}
): CssProcessor {
    return new CssProcessor(input, output, options);
}

declare type CssOptions = ProcessorOptions & {
    clean?: boolean;

    // Todo: implement this
    rebase?: boolean;
};

declare type CssOptions_2 = ProcessorOptions_2 & {
    clean?: boolean;
    rebase?: boolean;
};

declare class CssProcessor implements ProcessorInterface_2 {
    constructor(protected input: TaskInput, protected output?: TaskOutput, protected options: CssOptions = {}) {
    }

    config(taskName: string, builder: ConfigBuilder_2): BuildTask_2[] {
        return handleForceArray(this.input, (input) => {
            const task = builder.addTask(input, taskName);

            builder.assetFileNamesCallbacks.push((assetInfo) => {
                const name = assetInfo.names[0];

                if (!name) {
                    return undefined;
                }

                // Rename only if the asset name matches the task id with .css extension
                if (basename(name, '.css') === task.id) {
                    if (!this.output) {
                        return parse(input).name + '.css';
                    }

                    return task.normalizeOutput(this.output, '.css');

                    // if (!isAbsolute(name)) {
                    //   return name;
                    // } else {
                    //   builder.moveFilesMap[task.id + '.css'] = name;
                    // }
                }
            });

            return task;
        });
    }

    preview(): MaybePromise_2<ProcessorPreview_2[]> {
        return forceArray(this.input).map((input) => {
            return {
                input,
                output: this.output || basename(input),
                extra: {}
            };
        });
    }
}

declare class CssProcessor_2 implements ProcessorInterface {
    protected input: TaskInput_2;
    protected output?: TaskOutput_2 | undefined;
    protected options: CssOptions_2;
    constructor(input: TaskInput_2, output?: TaskOutput_2 | undefined, options?: CssOptions_2);
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
    external: typeof external_2;
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

declare function external_2(match: string, varName?: string): void;
export { external_2 as external }

declare type ExtraViteOptions = OverrideOptions<ConfigBuilder_2>;

declare type ExtraViteOptions_2 = OverrideOptions_2<ConfigBuilder>;

declare type FileTask<T extends keyof FileTaskOptionTypes = 'none'> = {
    src: string;
    dest: string;
    options: FileTaskOptionTypes[T];
};

declare type FileTask_2<T extends keyof FileTaskOptionTypes_2 = 'none'> = { src: string; dest: string; options: FileTaskOptionTypes_2[T] };

declare type FileTaskOptionTypes = {
    'none': any;
    'move': any;
    'copy': any;
    'link': LinkOptions;
};

declare type FileTaskOptionTypes_2 = {
    'none': any,
    'move': any,
    'copy': any,
    'link': LinkOptions_2,
}

declare type FileTasks<T extends keyof FileTaskOptionTypes = 'none'> = FileTask<T>[];

declare type FileTasks_2<T extends keyof FileTaskOptionTypes_2 = 'none'> = FileTask_2<T>[];

export declare function fileToId(input: string, group?: string): string;

declare function fileToId_2(input: string, group?: string) {
    input = normalize(input);

    group ||= randomBytes(4).toString('hex');

    return group + '-' + shortHash(input);
}

export declare function fullReloads(...paths: string[]): void;

declare namespace fusion {
    export {
        isVerbose_2 as isVerbose,
        isDev_2 as isDev,
        isProd_2 as isProd,
        params_2 as params,
        isWindows_2 as isWindows,
        shortHash_2 as shortHash,
        copyGlob_2 as copyGlob,
        moveGlob_2 as moveGlob,
        symlink_2 as symlink,
        fileToId_2 as fileToId,
        getGlobBaseFromPattern_2 as getGlobBaseFromPattern,
        FusionPlugin_2 as FusionPlugin,
        MaybePromise_2 as MaybePromise,
        MaybeArray_2 as MaybeArray,
        BuildTask_2 as BuildTask,
        ConfigBuilder_2 as ConfigBuilder,
        css_2 as css,
        js_2 as js,
        move_2 as move,
        copy_2 as copy,
        link_2 as link,
        callback_2 as callback,
        callbackAfterBuild_2 as callbackAfterBuild,
        ProcessorPreview_2 as ProcessorPreview,
        ProcessorInterface_2 as ProcessorInterface
    }
}

declare type Fusionfile = Record<string, any> | (() => Promise<Record<string, any>>);

declare type Fusionfile_2 = Record<string, any> | (() => Promise<Record<string, any>>);

export declare type FusionPlugin = PluginOption & {
    buildConfig?: (builder: ConfigBuilder) => MaybePromise<any>;
};

declare type FusionPlugin_2 = PluginOption & {
    buildConfig?: (builder: ConfigBuilder_2) => MaybePromise_2<any>;
}

declare interface FusionPluginOptions {
    fusionfile?: string | Fusionfile;
    chunkDir?: string;
    chunkNameObfuscation?: boolean;
    plugins?: FusionPlugin[];
    cliParams?: RunnerCliParams;
}

declare interface FusionPluginOptions_2 {
    fusionfile?: string | Fusionfile_2;
    chunkDir?: string;
    chunkNameObfuscation?: boolean;
    plugins?: FusionPlugin_2[];
    cliParams?: RunnerCliParams_2;
}

declare type FusionPluginOptionsUnresolved = FusionPluginOptions | string | (() => MaybePromise<Record<string, any>>);

export declare function getGlobBaseFromPattern(pattern: string): string;

declare function getGlobBaseFromPattern_2(pattern: string) {
    const specialChars = ["*", "?", "[", "]"];
    const idx = [...pattern].findIndex(c => specialChars.includes(c));

    if (idx === -1) {
        return dirname(pattern);
    }

    return dirname(pattern.slice(0, idx + 1));
}

export declare const isDev: boolean;

declare const isDev_2 = !isProd;

export declare const isProd: boolean;

declare const isProd_2 = process.env.NODE_ENV === 'production';

export declare let isVerbose: boolean;

declare let isVerbose_2 = false;

export declare function isWindows(): boolean;

declare function isWindows_2() {
    return process.platform === 'win32';
}

export declare function js(input: TaskInput_2, output?: TaskOutput_2): ProcessorInterface;

declare function js_2(input: TaskInput, output?: TaskOutput): ProcessorInterface_2 {
    return new JsProcessor(input, output);
}

export declare function link(input: TaskInput_2, dest: string, options?: LinkOptions): LinkProcessor;

declare function link_2(input: TaskInput, dest: string, options: LinkOptions_2 = {}) {
    return new LinkProcessor(input, dest, options);
}

declare interface LinkOptions {
    force?: boolean;
}

declare interface LinkOptions_2 {
    force?: boolean;
}

declare class LinkProcessor implements ProcessorInterface {
    input: TaskInput_2;
    dest: string;
    options: LinkOptions;
    constructor(input: TaskInput_2, dest: string, options?: LinkOptions);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare type MaybeArray<T> = T | T[];

declare type MaybeArray_2<T> = T | T[];

export declare type MaybePromise<T> = T | Promise<T>;

declare type MaybePromise_2<T> = T | Promise<T>;

export declare function move(input: TaskInput_2, dest: string): MoveProcessor;

declare function move_2(input: TaskInput, dest: string) {
    return new MoveProcessor(input, dest);
}

export declare function moveGlob(src: string, dest: string): Promise<void>;

declare async function moveGlob_2(src: string, dest: string): Promise<void> {
    const promises = handleFilesOperation(
    src,
    dest,
        {
        outDir: process.cwd(),
        handler: async (src, dest) => fs.move(src, dest, { overwrite: true }),
        globOptions: { onlyFiles: true }
    }
    );

    await Promise.all(promises);
}

declare class MoveProcessor implements ProcessorInterface {
    input: TaskInput_2;
    dest: string;
    constructor(input: TaskInput_2, dest: string);
    config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

export declare function outDir(outDir: string): void;

declare type OverrideOptions <T> = Partial<T> | ((options: Partial<T>) => T | undefined);

export declare function overrideOptions(options: FusionPluginOptions): FusionPluginOptions;

declare type OverrideOptions_2<T> = Partial<T> | ((options: Partial<T>) => T | undefined);

export declare function overrideViteConfig(config: UserConfig | null): void;

export declare let params: RunnerCliParams | undefined;

declare let params_2: RunnerCliParams_2 | undefined = undefined;

export declare function plugin(...plugins: FusionPlugin[]): void;

export declare interface ProcessorInterface {
    config(taskName: string, builder: ConfigBuilder): MaybePromise<any>;
    preview(): MaybePromise<ProcessorPreview[]>;
}

declare interface ProcessorInterface_2 {
    config(taskName: string, builder: ConfigBuilder_2): MaybePromise_2<any>;

    preview(): MaybePromise_2<ProcessorPreview_2[]>;
}

declare interface ProcessorOptions {
    vite?: ExtraViteOptions;
    verbose?: boolean;
}

declare interface ProcessorOptions_2 {
    vite?: ExtraViteOptions_2;
    verbose?: boolean;
}

export declare type ProcessorPreview = {
    input: string;
    output: string;
    extra?: Record<string, any>;
};

declare type ProcessorPreview_2 = {
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

declare type RunnerCliOptions_2 = {
    // w?: boolean;
    // watch?: boolean;
    cwd?: string;
    l?: boolean;
    list?: boolean;
    c?: string;
    config?: string;
    v?: number;
    verbose?: number;
    serverFile?: string;
    s?: string;
    // series?: boolean;
    // s?: boolean;
}

declare type RunnerCliParams = Arguments<RunnerCliOptions>;

declare type RunnerCliParams_2 = Arguments<RunnerCliOptions_2>;

export declare function shortHash(bufferOrString: default_2.BinaryLike, short?: number | null): string;

declare function shortHash_2(bufferOrString: Crypto_2.BinaryLike, short: number | null = 8): string {
    let hash = Crypto.createHash('sha1')
    .update(bufferOrString)
    .digest('hex');

    if (short && short > 0) {
        hash = hash.substring(0, short);
    }

    return hash;
}

export declare function symlink(target: string, link: string, force?: boolean): Promise<void>;

declare async function symlink_2(target: string, link: string, force = false) {
    target = resolve(target);
    link = resolve(link);

    if (isWindows() && !fs.lstatSync(target).isFile()) {
        return fs.ensureSymlink(target, link, 'junction');
    }

    if (isWindows() && fs.lstatSync(target).isFile() && force) {
        return fs.ensureLink(target, link);
    }

    return fs.ensureSymlink(target, link);
}

declare type TaskInput = string | string[];

declare type TaskInput_2 = string | string[];

declare type TaskOutput = string;

declare type TaskOutput_2 = string;

export declare function useFusion(fusionOptions?: FusionPluginOptionsUnresolved, tasks?: string | string[]): PluginOption;

export { }
