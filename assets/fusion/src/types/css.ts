import { ProcessorOptions } from './processors';

export type CssOptions = ProcessorOptions & {
  clean?: boolean;

  // Todo: implement this
  rebase?: boolean;
};
