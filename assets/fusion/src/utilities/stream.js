/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import path from 'path';
import through from 'through2';

export function resetBaseToFirst(processor) {
  return through.obj((file, enc, cb) => {
    if (Array.isArray(processor.source)) {
      const src = processor.source[0];
      file.base = path.dirname(src);
      file.cwdbase = path.dirname(src);
    }

    cb(null, file);
  });
}
