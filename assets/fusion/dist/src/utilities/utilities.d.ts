import { OverrideOptions } from '../types';
import { OutputOptions } from 'rollup';
export declare function mergeOptions<T extends Record<string, any> = Record<string, any>>(base: T, ...overrides: (OverrideOptions<T> | undefined)[]): T;
export declare function appendMinFileName(output: OutputOptions): OutputOptions;
export declare function show(data: any, depth?: number): void;
