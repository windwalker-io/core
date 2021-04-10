/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import fs from 'fs';
import { mergeWith } from 'lodash-es';
import path from 'path';

/**
 * Merge object and concat array
 * @see https://lodash.com/docs/4.17.15#mergeWith
 *
 * @param args
 */
export function merge(...args) {
  return mergeWith(
    ...args,
    (objValue, srcValue) => {
      if (Array.isArray(objValue)) {
        return objValue.concat(srcValue);
      }
    }
  );
}

export function extractDest(dest) {
  let merge = dest !== null
    && (dest.slice(-1) !== '/'
    || (fs.existsSync(dest) && !fs.lstatSync(dest).isDirectory()));
  let destFile;
  let destPath;
  let samePosition = false;

  if (merge) {
    destFile = path.basename(dest);
    destPath = path.dirname(dest);
  } else if (dest === null) {
    destPath = file => file.base;
    samePosition = true;
  } else {
    destPath = dest;
  }

  return {
    merge,
    samePosition,
    file: destFile,
    path: destPath
  };
}

