/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import CssPreProcessor from './css-pre-processor.js';
import { default as gulpSass } from 'gulp-sass'

export default class SassProcessor extends CssPreProcessor {
  compile(dest, options = {}) {
    this.pipe(
      gulpSass({ style: 'expanded' })
        .on('error', gulpSass.logError)
    );
    return this;
  }
}
