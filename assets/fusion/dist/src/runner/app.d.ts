import { RunnerCliParams } from '../types/runner';
export declare function getArgsAfterDoubleDashes(argv?: string[]): string[];
export declare function parseArgv(argv: string[]): RunnerCliParams;
export declare function runApp(argv: RunnerCliParams): Promise<void>;
export declare function processApp(params: RunnerCliParams): Promise<void>;
