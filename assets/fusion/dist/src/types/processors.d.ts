import { UserConfig } from 'vite';
export type TaskInput = string | string[];
export type TaskOutput = string;
export interface ProcessorOptions {
    vite?: ExtraViteOptions;
    verbose?: boolean;
}
export type OverrideOptions<T> = Partial<T> | ((options: Partial<T>) => Partial<T> | undefined);
export type ExtraViteOptions = OverrideOptions<UserConfig>;
