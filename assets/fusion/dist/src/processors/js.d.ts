import { JsOptions, TaskInput, TaskOutput } from '../types';
import { OutputOptions } from 'rollup';
import { UserConfig } from 'vite';
export declare function js(input: TaskInput, output: TaskOutput, options?: JsOptions): Promise<UserConfig[]>;
export declare function useJsProcessor(output: TaskOutput, options: JsOptions, createOptions: (outputs: OutputOptions[], isMinify: boolean) => UserConfig): UserConfig[];
export declare function overrideViteJsOptions(config: UserConfig, options: JsOptions): UserConfig;
export declare function addExternals(config: UserConfig, externals?: Record<string, string>): UserConfig;
