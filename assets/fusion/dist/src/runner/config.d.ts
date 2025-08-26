import { UserConfig } from 'vite';
import { ConfigResult, LoadedConfigTask, RunnerCliParams } from '../types/runner';
export declare function loadConfigFile(configFile: ConfigResult): Promise<Record<string, LoadedConfigTask>>;
export declare function resolveTaskOptions(task: LoadedConfigTask, resolveSubFunctions?: boolean): Promise<UserConfig[]>;
export declare function mustGetAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult;
export declare function getAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult | null;
export declare function findDefaultConfig(root: string): ConfigResult | null;
