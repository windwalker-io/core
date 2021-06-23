/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import concat from 'gulp-concat';
import eol from 'gulp-eol';
import filter from 'gulp-filter';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import rewriteCSS from 'gulp-rewrite-css';
import sourcemaps from 'gulp-sourcemaps';
import { dest as toDest } from '../base/base.js';
import { MinifyOption } from '../config.js';
import { logError } from '../utilities/error.js';
import { resetBaseToFirst } from '../utilities/stream.js';
import { merge } from '../utilities/utilities.js';
import Processor from './processor.js';

export default class CssPreProcessor extends Processor {

  async prepareOptions(options = {}) {
    return merge(
      {},
      {
        sourcemap: true,
        autoprefixer: true,
        minify: MinifyOption.DEFAULT,
        rebase: false
      },
      options
    );
  }

  compile(dest, options = {}) {
    throw new Error('Please implement this method.');
  }

  doProcess(dest, options = {}) {
    this.pipe(eol('\n', true))
      .pipeIf(options.sourcemap, () => sourcemaps.init())
      .pipeIf(dest.merge, () => resetBaseToFirst(this))
      .pipeIf(dest.merge, () => concat(dest.file))
      .compile(dest, options)
      .pipeIf(
        options.rebase && !dest.samePosition,
        () => rewriteCSS({ destination: dest.path })
      )
      .pipeIf(options.postcss, () => postcss(options.postcss, options.postcss?.config || {}))
      .pipeIf(
        options.autoprefixer,
        () => autoprefixer('last 3 version', 'safari 5', 'ie 8', 'ie 9')
          .on('error', logError())
      )
      .pipeIf(
        options.minify === MinifyOption.SAME_FILE,
        () => cleanCSS({ compatibility: 'ie11' })
      )
      .pipeIf(
        options.sourcemap,
        () => sourcemaps.write('.')
      )
      .pipe(toDest(dest.path))
      .pipeIf(options.minify === MinifyOption.SEPARATE_FILE, () => {
        this.pipe(filter('**/*.css'))
          .pipe(rename({ suffix: '.min' }))
          .pipe(cleanCSS({ compatibility: 'ie11' }))
          .pipe(toDest(dest.path));
      });
  }
}
