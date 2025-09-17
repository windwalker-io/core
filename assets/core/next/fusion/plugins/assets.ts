import { ConfigBuilder, type FusionPlugin } from '@windwalker-io/fusion-next';
import isGlob from 'is-glob';
import micromatch from 'micromatch';
import { normalize } from 'node:path';
import { relative } from 'path';
import { containsMiddleGlob, removeLastGlob, uniqId } from '../../utilities';
import { getAvailableForReposition, handleCloneAssets, handleReposition } from '../processors/cloneAssets';

export interface WindwalkerAssetsOptions {
  clone?: Record<string, string>;
  reposition?: Record<string, string>;
}

export function windwalkerAssets(options: WindwalkerAssetsOptions): FusionPlugin {
  return {
    name: 'ww:assets',
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
