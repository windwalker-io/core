import { MinifyOptions } from '@/enum';
import { OverrideOptions, ProcessorOptions } from './processors';
import { type AcceptedPlugin, type ProcessOptions } from 'postcss';

export type CssOptions = ProcessorOptions & {
  minify?: MinifyOptions | boolean;
  browserslist?: string | string[];
  postcss?: OverrideOptions<ProcessOptions & AcceptedPlugin>;
  clean?: boolean;

  // Todo: implement this
  rebase?: boolean;
};
