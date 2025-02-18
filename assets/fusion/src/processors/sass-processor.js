/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import CssPreProcessor from './css-pre-processor.js';
import { default as gulpSass } from 'gulp-sass';
import * as sass from 'sass';

const gulpSassInc = gulpSass(sass);

export default class SassProcessor extends CssPreProcessor {
  compile(dest, options = {}) {
    const sassOptions = options.sass || {};

    this.pipe(
      gulpSassInc({ style: 'expanded', ...sassOptions })
        .on('error', gulpSassInc.logError)
    );
    return this;
  }
}
