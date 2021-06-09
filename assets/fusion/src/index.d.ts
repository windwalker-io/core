import { DestMethod, SrcMethod, WatchMethod, lastRun as lr } from 'gulp';
import { Gulp } from 'gulp';
import { Settings } from 'gulp-typescript';
import * as vfs from 'vinyl-fs';
import { WebpackOptionsNormalized } from 'webpack/types';

declare namespace Fusion {
  export interface DestOptions {
    merge: boolean;
    samePosition: boolean;
    file: string,
    path: string
  }

  export type MiniOptions = string | 'none' | 'same_file' | 'separate_file';

  export type taskProcessor<T> = (source: string | Array<string>,
                                  dest?: string | Array<string>,
                                  options?: T) => Promise<NodeJS.ReadWriteStream>;

  export interface Processor<O> {
    options: O;

    new(source: string | Array<string>, options?: O);

    process(dest: string | Array<string>): Promise<NodeJS.ReadWriteStream>;
  }

  export interface CssOptions {
    autoprefixer?: boolean;
    minify?: MiniOptions;
    rebase?: true;
    postcss: any;
  }

  export interface CssPreProcessorOptions extends CssOptions {
    sourcemap?: boolean;
  }

  export interface JsOptions {
    sourcemap?: boolean;
    minify?: MiniOptions;
  }

  export interface BabelOptions extends JsOptions {
    targets?: string;
    babel?: BabelOptions;
    module?: string | 'systemjs' | 'umd' | 'amd';
    version?: boolean | string
  }

  export interface ModuleOptions extends BabelOptions {
    es5?: string | boolean;
  }

  export interface TsOptions extends JsOptions {
    ts: Settings
  }

  export interface WebpackOptions extends JsOptions {
    webpack?: WebpackOptionsNormalized;
    override?: WebpackOptionsNormalized | Function;
    merge?: WebpackOptionsNormalized;
  }

  export interface RollupOptions extends JsOptions {
    rollup?: WebpackOptionsNormalized;
    override?: WebpackOptionsNormalized | Function;
    merge?: WebpackOptionsNormalized;
  }

  export interface VueOptions extends WebpackOptions {
    root?: string;
  }

  export const src: SrcMethod;
  export const dest: DestMethod;
  export const symlink: typeof vfs.symlink;
  export const watch: WatchMethod;
  export const lastRun: typeof lr;
  export const copy: taskProcessor<CssOptions>;
  export function livereload(source: string | Array<string>, options: any): Promise<NodeJS.ReadWriteStream>;
  export function waitAllEnded(...args: Array<Promise<NodeJS.ReadWriteStream>>): Promise<NodeJS.ReadWriteStream[]>;
  export function waitFirstEnded(...args: Array<Promise<NodeJS.ReadWriteStream>>): Promise<NodeJS.ReadWriteStream>;

  export const css: taskProcessor<CssOptions>;
  export const CssProcessor: Processor<CssOptions>;
  export const sass: taskProcessor<CssPreProcessorOptions>;
  export const SassProcessor: Processor<CssPreProcessorOptions>;
  export const js: taskProcessor<JsOptions>;
  export const JsProcessor: Processor<JsOptions>;
  export const babel: taskProcessor<BabelOptions>;
  export const BabelProcessor: Processor<BabelOptions>;
  export const module: taskProcessor<ModuleOptions>;
  export const ModuleProcessor: Processor<ModuleOptions>;
  export const ts: taskProcessor<TsOptions>;
  export const TsProcessor: Processor<TsOptions>;
  export const webpack: taskProcessor<WebpackOptions>;
  export const WebpackProcessor: Processor<WebpackOptions>;
  export const rollup: taskProcessor<RollupOptions>;
  export const RollupProcessor: Processor<RollupOptions>;
  export const vue: taskProcessor<VueOptions>;
  export const VueProcessor: Processor<VueOptions>;
}

export = Fusion;
