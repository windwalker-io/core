import { MinifyOptions } from '../../enum/MinifyOptions.ts';

export interface CssOptions {
  autoprefixer?: boolean;
  minify?: MinifyOptions;
  rebase?: true;
  postcss?: any;
}
