import { PostCSSPluginConf } from 'rollup-plugin-postcss';
import { MinifyOptions } from '@/enum';
import { OverrideOptions, ProcessorOptions } from './processors';

export type CssOptions = ProcessorOptions & {
  minify?: MinifyOptions | boolean;
  browserslist?: string | string[];
  postcss: OverrideOptions<PostCSSPluginConf>;

  // Todo: implement this
  rebase?: boolean;
};
