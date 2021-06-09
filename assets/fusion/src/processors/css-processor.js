/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import concat from 'gulp-concat';
import filter from 'gulp-filter';
import rename from 'gulp-rename';
import postcss from 'gulp-postcss';
import rewriteCSS from 'gulp-rewrite-css';
import { dest as toDest } from '../base/base.js';
import { MinifyOption } from '../config.js';
import { logError } from '../utilities/error.js';
import { merge } from '../utilities/utilities.js';
import Processor from './processor.js';

export default class CssProcessor extends Processor {
  async prepareOptions(options = {}) {
    return merge(
      {},
      {
        autoprefixer: true,
        postcss: null,
        minify: MinifyOption.DEFAULT,
        rebase: true
      },
      options
    );
  }

  doProcess(dest, options = {}) {
    this.pipeIf(options.rebase, () => rewriteCSS({ destination: dest.path }))
      .pipeIf(dest.merge, () => concat(dest.file))
      .pipe(postcss(options.postcss, options.postcss?.config || {}))
      .pipeIf(
        options.autoprefixer,
        () => autoprefixer(
          'last 3 version',
          'safari 5',
          'ie 9-11'
        ).on('error', logError())
      )
      .pipeIf(options.minify === MinifyOption.SAME_FILE, () => cleanCSS({ compatibility: 'ie11' }))
      .pipe(toDest(dest.path))
      .pipeIf(options.minify === MinifyOption.SEPARATE_FILE, () => [
        filter('**/*.css'),
        rename({ suffix: '.min' }),
        cleanCSS({ compatibility: 'ie11' }),
        toDest(dest.path)
      ]);
  }
}
