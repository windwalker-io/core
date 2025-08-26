import { OutputOptions } from 'rollup';
import { OverrideOptions } from '../types';
import { UserConfig } from 'vite';
export declare function mergeOptions<T = UserConfig>(base: Partial<T> | undefined, ...overrides: (OverrideOptions<T> | undefined)[]): Partial<T>;
export declare function appendMinFileName(output: OutputOptions): OutputOptions;
