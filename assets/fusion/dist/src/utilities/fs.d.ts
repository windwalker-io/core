import { FileTasks } from '../types';
import { Logger } from 'vite';
export declare function moveFilesAndLog(tasks: FileTasks, outDir: string, logger: Logger): Promise<any[]>;
export declare function copyFilesAndLog(tasks: FileTasks, outDir: string, logger: Logger): Promise<any[]>;
export declare function linkFilesAndLog(tasks: FileTasks<'link'>, outDir: string, logger: Logger): Promise<any[]>;
export declare function symlink(target: string, link: string, force?: boolean): Promise<void>;
export declare function endsWithSlash(path: string): boolean;
