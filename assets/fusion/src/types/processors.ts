import { InputOption, OutputOptions, RollupOptions } from 'rollup';

export type TaskInput = InputOption;
export type TaskOutput = OutputOptions | string;

export type ExtraOptions = Partial<RollupOptions> | ((options: Partial<RollupOptions>) => Partial<RollupOptions> | undefined);
