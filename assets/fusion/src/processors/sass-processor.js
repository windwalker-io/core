/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import CssPreProcessor from './css-pre-processor.js';
import { default as gulpSass } from 'gulp-sass';
import sass from 'sass';

const gulpSassInc = gulpSass(sass);

export default class SassProcessor extends CssPreProcessor {
  compile(dest, options = {}) {
    this.pipe(
      gulpSassInc({ style: 'expanded' })
        .on('error', gulpSassInc.logError)
    );
    return this;
  }
}
