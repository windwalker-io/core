import { MaybeArray } from 'rollup';
export declare function forceArray<T>(item: T | T[]): T[];
export declare function handleMaybeArray<T, R>(items: T | T[], callback: (item: T) => R): T extends any[] ? R[] : R;
export declare function appendToMaybeArray<T>(items: MaybeArray<T>, value: T): T[];
