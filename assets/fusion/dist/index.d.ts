import * as rollup from 'rollup';
import { InputOption, OutputOptions, RollupOptions, ModuleFormat } from 'rollup';
import { CleanHandler } from '@/plugins/clean';
import { PostCSSPluginConf } from 'rollup-plugin-postcss';
import { MinifyOptions } from '@/enum';
import { ProcessorOptions as ProcessorOptions$1, OverrideOptions as OverrideOptions$1 } from '@/types/processors';
import { Arguments } from 'yargs';
import * as fusion from '@/dep';
export * from '@/dep';
export { params } from '@/runner';

type TaskInput = InputOption;
type TaskOutput = OutputOptions | OutputOptions[] | string;
interface ProcessorOptions {
    rollup?: ExtraRollupOptions;
    verbose?: boolean;
}
type OverrideOptions<T> = Partial<T> | ((options: Partial<T>) => Partial<T> | undefined);
type ExtraRollupOptions = OverrideOptions<RollupOptions>;

type CssOptions = ProcessorOptions & {
  minify?: MinifyOptions | boolean;
  browserslist?: string | string[];
  postcss?: OverrideOptions<PostCSSPluginConf>;
  clean?: CleanHandler;

  // Todo: implement this
  rebase?: boolean;
};

type JsOptions = ProcessorOptions$1 & {
  /**
   * Minify options or boolean to enable with default options.
   */
  minify?: MinifyOptions;
  /**
   * Babel options or function returning options to override esbuild's default Babel config.
   */
  babel?: OverrideOptions$1<any>;
  /**
   * Output format. Default is `es`.
   */
  format?: ModuleFormat;
  /**
   * Global name for UMD build. Only used when `format` is `umd`.
   */
  umdName?: string;
  /**
   * Clean output dir before build.
   */
  clean?: CleanHandler;
  /**
   * Path to tsconfig.json. Set false to disable it.
   * @default './tsconfig.json'
   */
  tsconfig?: string | false;
  /**
   * Target JS env for esbuild.
   * @see https://esbuild.github.io/api/#target
   */
  target?: string | string[];
};

type RunnerCliOptions = {
  w?: boolean;
  watch?: boolean;
  cwd?: string;
  l?: boolean;
  list?: boolean;
  c?: string;
  config?: string;
  v?: number;
  verbose?: number;
}
type RunnerCliParams = Arguments<RunnerCliOptions>;

declare const isVerbose: boolean;

declare const _default: {
    params: RunnerCliParams;
    MinifyOptions: typeof fusion.MinifyOptions;
    css(input: TaskInput, output: TaskOutput, options?: CssOptions): Promise<rollup.MaybeArray<rollup.RollupOptions>>;
    js(input: TaskInput, output: TaskOutput, options?: JsOptions): Promise<rollup.RollupOptions[]>;
};

export { _default as default, isVerbose };
