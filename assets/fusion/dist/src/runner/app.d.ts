import { RunnerCliParams } from '../types/runner';
export declare function parseArgv(): RunnerCliParams;
export declare function runApp(argv: RunnerCliParams): Promise<void>;
export declare function processApp(params: RunnerCliParams): Promise<void>;
