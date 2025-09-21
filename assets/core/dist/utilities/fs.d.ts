export declare function loadJson(file: string): any;
export declare function containsMiddleGlob(str: string): boolean;
export declare function removeLastGlob(str: string): string;
export declare function ensureDirPath(path: string, slash?: '/' | '\\'): string;
export interface FindFileResult {
    fullpath: string;
    relativePath: string;
}
export declare function findFilesFromGlobArray(sources: string[]): FindFileResult[];
