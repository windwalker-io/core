import { OverrideOptions, ProcessorOptions } from '@/types/processors';
import { ModuleFormat, OutputOptions } from 'rollup';

export type JsOptions = ProcessorOptions & {
  minify?: MiniOptions;
  babel?: OverrideOptions<any>;
  format?: ModuleFormat | OutputOptions;
  umdName?: string;
  clean?: boolean;
};
