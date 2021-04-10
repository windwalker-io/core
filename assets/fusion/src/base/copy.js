/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { postStream, prepareStream } from '../lifecycles.js';
import { extractDest } from '../utilities/utilities.js';
import { src, dest as toDest } from './base.js';
import rename from 'gulp-rename';
import path from 'path';

export async function copy(source, dest, options = {}) {
  let stream = prepareStream(src(source));

  dest = extractDest(dest);

  if (dest.merge) {
    stream = stream.pipe(rename(path.basename(dest.file)));
  }

  stream = stream.pipe(toDest(dest.path).on('error', e => console.error(e)));

  return postStream(stream);
}
