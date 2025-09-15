import { default as ConfigBuilder } from '../builder/ConfigBuilder.ts';
export type TaskInput = string | string[];
export type TaskOutput = string;
export interface ProcessorOptions {
    vite?: ExtraViteOptions;
    verbose?: boolean;
}
export type OverrideOptions<T> = Partial<T> | ((options: Partial<T>) => T | undefined);
export type ExtraViteOptions = OverrideOptions<ConfigBuilder>;
