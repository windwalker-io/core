import { DestMethod, SrcMethod, WatchMethod, parallel as gulpParallel, series as gulpSeries, lastRun as lr } from 'gulp';
import { Gulp } from 'gulp';
import { Settings } from 'gulp-typescript';
import * as vfs from 'vinyl-fs';
import { WebpackOptionsNormalized } from 'webpack/types';
import { BabelOptions } from './utilities/babel';

declare module "@windwalker-io/fusion" {

}

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
    rename: any;
  }

  export interface BabelProcessorOptions extends JsOptions {
    targets?: string;
    ie?: boolean;
    babel?: BabelOptions;
    module?: string | 'systemjs' | 'umd' | 'amd';
    version?: boolean | string
  }

  export interface ModuleOptions extends BabelProcessorOptions {
    es5?: string | boolean;
  }

  export interface TsOptions extends JsOptions {
    ts?: Settings,
    tsconfig?: string | Function;
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
    excludeVue?: boolean;
  }

  export const src: SrcMethod;
  export const dest: DestMethod;
  export const symlink: typeof vfs.symlink;
  export const watch: WatchMethod;
  export const lastRun: typeof lr;
  export const copy: taskProcessor<CssOptions>;
  export function livereload(source: string | Array<string>, options: any): Promise<NodeJS.ReadWriteStream>;
  export function wait(...args: Array<Promise<NodeJS.ReadWriteStream>>): Promise<NodeJS.ReadWriteStream[]>;
  export function waitFirst(...args: Array<Promise<NodeJS.ReadWriteStream>>): Promise<NodeJS.ReadWriteStream>;

  export const css: taskProcessor<CssOptions>;
  export const CssProcessor: Processor<CssOptions>;
  export const sass: taskProcessor<CssPreProcessorOptions>;
  export const SassProcessor: Processor<CssPreProcessorOptions>;
  export const js: taskProcessor<JsOptions>;
  export const JsProcessor: Processor<JsOptions>;
  export const babel: taskProcessor<BabelProcessorOptions>;
  export const BabelProcessor: Processor<BabelProcessorOptions>;
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

  // Bundlers
  export const webpackBundle: (file: string, dest: string, options: (config: WebpackOptions) => any) => Promise<any>;
  export const webpackVueBundle: (file: string, dest: string, options: (config: WebpackOptions) => any) => Promise<any>;

  // Gulp
  export const parallel: typeof gulpParallel;
  export const series: typeof gulpSeries;
}

export = Fusion;
