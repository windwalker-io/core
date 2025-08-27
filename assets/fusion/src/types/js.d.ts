import { MinifyOptions } from '@/enum';
import { CleanHandler } from '@/plugins/clean';
import { OverrideOptions, ProcessorOptions } from '@/types/processors';
import { TsconfigRaw } from 'esbuild';
import { ModuleFormat } from 'rollup';
import { ESBuildOptions } from 'vite';

export interface JsOptions extends ProcessorOptions {
  /**
   * Minify options or boolean to enable with default options.
   */
  minify?: MinifyOptions;
  /**
   * Babel options or function returning options to override esbuild's default Babel config.
   */
  babel?: OverrideOptions<any>;
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
   * esbuild() config override.
   */
  esbuild?: OverrideOptions<ESBuildOptions>;
  // /**
  //  * Path to tsconfig.json. Set false to disable it.
  //  * @default './tsconfig.json'
  //  */
  // tsconfig?: TsconfigRaw | string;
  /**
   * Target JS env for esbuild.
   * @see https://esbuild.github.io/api/#target
   */
  target?: string | string[];
  /**
   * Externals with instead variables for rollup, use webpack external syntax.
   */
  externals?: Record<string, string>;
  /**
   * Path alias for module resolution, if send a string, it will be resolved as `@/*`.
   */
  path?: string | Record<string, string>;
}
