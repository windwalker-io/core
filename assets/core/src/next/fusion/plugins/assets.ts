import { type FusionPlugin } from '@windwalker-io/fusion-next';
import { getAvailableForReposition, handleCloneAssets, handleReposition } from '../processors/cloneAssets';

export interface WindwalkerAssetsOptions {
  clone?: Record<string, string>;
  reposition?: Record<string, string>;
}

export function globalAssets(options: WindwalkerAssetsOptions): FusionPlugin {
  return {
    name: 'core:global-assets',
    buildConfig(builder) {
      const clone = options.clone || {};
      let reposition = options.reposition || {};

      reposition = { ...reposition, ...getAvailableForReposition(clone) };

      // Handle reposition
      handleReposition(builder, reposition);

      const clonePatterns = Object.keys(clone);

      // Handle clone
      if (clonePatterns.length > 0) {
        handleCloneAssets(builder, clonePatterns);
      }
    }
  };
}
