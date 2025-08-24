import { InputOption, OutputOptions, RollupOptions } from 'rollup';

export type TaskInput = InputOption;
export type TaskOutput = OutputOptions | OutputOptions[] | string;

export interface ProcessorOptions {
  rollup?: ExtraRollupOptions;
}
export type OverrideOptions <T> = Partial<T> | ((options: Partial<T>) => Partial<T> | undefined);
export type ExtraRollupOptions = OverrideOptions<RollupOptions>;
