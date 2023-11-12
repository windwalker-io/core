/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import filter from 'gulp-filter';
import rename from 'gulp-rename';
import sourcemaps from 'gulp-sourcemaps';
import stripComment from 'gulp-strip-comments';
import terser from 'gulp-terser';
import gulpTs from 'gulp-typescript';
import { merge as lodashMerge } from 'lodash-es';
import { dest as toDest } from '../base/base.js';
import { MinifyOption } from '../config.js';
import { merge } from '../utilities/utilities.js';
import JsProcessor from './js-processor.js';

export default class TsProcessor extends JsProcessor {
  async prepareOptions(options) {
    options = merge(
      {
        ts: {
          declaration: false,
          target: 'es2020',
          moduleResolution: 'node',
          allowJs: true,
        },
        tsconfig: null
      },
      await super.prepareOptions(options)
    );

    if (options.ts.target.toLocaleLowerCase() === 'es5') {
      options.ts.lib = options.ts.lib || ['es6', 'es7', 'dom', 'DOM.Iterable'];
    }

    return options;
  }

  compile(dest, options) {
    const config = lodashMerge({}, options.ts);
    let tsTask;
    
    if (options.tsconfig) {
      let tsconfig = options.tsconfig;
      if (typeof tsconfig === 'function') {
        tsconfig = tsconfig();
      }

      tsTask = gulpTs.createProject(tsconfig, config)();
    } else {
      tsTask = gulpTs(config);
    }

    return this.pipe(tsTask);
  }

  doProcess(dest, options = {}) {
    this.pipeIf(options.sourcemap, () => sourcemaps.init());

    if (dest.merge) {
      options.ts.outFile = dest.file;
      options.ts.module = options.ts.module || 'amd';
    }

    this.compile(dest, options)
      .pipeIf(options.sourcemap, () => sourcemaps.write('.'))
      .pipeIf(options.rename, () => rename(options.rename))
      .pipeIf(
        options.minify === MinifyOption.SAME_FILE,
        () => {
          this.pipe(filter('**/*.js'))
            .pipe(stripComment())
            .pipe(terser().on('error', e => console.error(e)));
        }
      )
      .pipe(toDest(dest.path))
      .pipeIf(options.minify === MinifyOption.SEPARATE_FILE, () => {
        this
          .pipe(stripComment())
          .pipe(filter('**/*.js'))
          .pipe(rename({ suffix: '.min' }))
          .pipe(terser().on('error', e => console.error(e)))
          .pipe(toDest(dest.path));
      });
  }
}
