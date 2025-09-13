import { OverrideOptions } from '../types';
import { OutputOptions } from 'rollup';
import { UserConfig } from 'vite';
export declare function mergeOptions<T = UserConfig>(base: Partial<T> | undefined, ...overrides: (OverrideOptions<T> | undefined)[]): Partial<T>;
export declare function appendMinFileName(output: OutputOptions): OutputOptions;
