import { ProcessorInterface } from '../processors/ProcessorInterface.ts';
import { ConfigResult, LoadedConfigTask, RunnerCliParams } from '../types/runner';
export declare function loadConfigFile(configFile: ConfigResult): Promise<Record<string, LoadedConfigTask>>;
export declare function expandModules(modules: Record<string, any>): Record<string, any>;
export declare function resolveTaskResults(task: LoadedConfigTask): Promise<(ProcessorInterface | (() => LoadedConfigTask))[]>;
export declare function resolveTaskOptions(task: LoadedConfigTask, resolveSubFunctions?: boolean): Promise<ProcessorInterface[]>;
export declare function mustGetAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult;
export declare function getAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult | null;
export declare function findDefaultConfig(root: string): ConfigResult | null;
