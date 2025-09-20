import { FusionPlugin } from '@windwalker-io/fusion-next';
export interface WindwalkerAssetsOptions {
    clone?: Record<string, string>;
    reposition?: Record<string, string>;
}
export declare function windwalkerAssets(options: WindwalkerAssetsOptions): FusionPlugin;
