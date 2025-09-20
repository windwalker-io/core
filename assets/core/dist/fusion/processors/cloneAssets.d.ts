import { ConfigBuilder } from '@windwalker-io/fusion-next';
export declare function cloneAssets(patterns: Record<string, string>): {
    config(taskName: string, builder: ConfigBuilder): import('@windwalker-io/fusion-next').MaybePromise<any>;
    preview(): import('@windwalker-io/fusion-next').MaybePromise<import('@windwalker-io/fusion-next').ProcessorPreview[]>;
};
export declare function getAvailableForReposition(patterns: Record<string, string>): Record<string, string>;
export declare function handleCloneAssets(builder: ConfigBuilder, clonePatterns: string[]): void;
export declare function handleReposition(builder: ConfigBuilder, reposition: Record<string, string>): void;
