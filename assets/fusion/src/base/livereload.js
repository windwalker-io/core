/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { src } from './base.js';
import gulpLivereload from 'gulp-livereload';

export async function livereload(source, options = {}) {
  return src(source).pipe(gulpLivereload(options));
}
