export declare function installVendors(npmVendors?: string[], to?: string): {
    config(taskName: string, builder: import('@windwalker-io/fusion-next').ConfigBuilder): import('@windwalker-io/fusion-next').MaybePromise<any>;
    preview(): import('@windwalker-io/fusion-next').MaybePromise<import('@windwalker-io/fusion-next').ProcessorPreview[]>;
};
export declare function findAndInstall(npmVendors?: string[], to?: string): Promise<void>;
