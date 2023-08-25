/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import concat from 'gulp-concat';
import eol from 'gulp-eol';
import filter from 'gulp-filter';
import rename from 'gulp-rename';
import sourcemaps from 'gulp-sourcemaps';
import stripComment from 'gulp-strip-comments';
import gulpTerser from 'gulp-terser';
import * as terser from 'terser';
import { dest as toDest } from '../base/base.js';
import { MinifyOption } from '../config.js';
import { merge } from '../utilities/utilities.js';
import through from 'through2';
import Processor from './processor.js';
import crypto from 'crypto';

// const uglify = gu.default;

export default class JsProcessor extends Processor {
  currentVersion;

  async prepareOptions(options = {}) {
    return merge(
      {},
      {
        sourcemap: true,
        minify: MinifyOption.DEFAULT,
        version: true,
        rename: null
      },
      options
    );
  }

  compile(dest, options) {
    //
    return this;
  }

  doProcess(dest, options) {
    this.pipe(eol('\n'))
      .pipeIf(options.version, () => this.appendVersion(options.version))
      .pipeIf(options.sourcemap, () => sourcemaps.init())
      .pipeIf(dest.merge, () => concat(dest.file))
      .compile(dest, options)
      .pipeIf(options.minify === MinifyOption.SAME_FILE, () => {
        this.pipe(filter('**/*.js'))
          .pipe(stripComment())
          .pipe(terserTask());
      })
      .pipeIf(options.sourcemap, () => sourcemaps.write('.'))
      .pipeIf(options.rename, () => rename(options.rename))
      .pipe(toDest(dest.path))

      // Run terser to validate ES syntax
      .pipe(filter('**/*.{js,mjs}'))
      .pipe(stripComment())
      .pipe(terserTask())
      .pipe(rename({ suffix: '.min' }))
      .pipeIf(options.minify === MinifyOption.SEPARATE_FILE, () => {
        this.pipe(toDest(dest.path));
      });
  }

  appendVersion(version) {
    return through.obj((file, enc, cb) => {
      if (file.isNull() || file.isDirectory()) {
        cb(null, file);
        return;
      }

      let js = file.contents.toString().replace(
        /(import )([\{\}\w\W,\s]+? from )?('(.*)')/gm,
        (match, imp, val, from, uri) => {
          if (from && uri) {
            uri = this.appendUriVersion(uri, version);
            from = `'${uri}'`;
          }

          val = val || '';

          return `${imp}${val}${from}`;
        }
      );

      js = js.replace(
        /(import\s*)\(\s*'(.*)'\s*\)/gm,
        (match, imp, uri) => {
          uri = this.appendUriVersion(uri, version);

          return `${imp}('${uri}')`;
        }
      );

      file.contents = new Buffer(js);
      cb(null, file);
    });
  }

  /**
   * @param {string} uri
   * @param {string} version
   */
  appendUriVersion(uri, version) {
    if (!uri) {
      return uri;
    }

    if (uri.endsWith('.js') || uri.endsWith('.mjs')) {
      if (uri.includes('?')) {
        uri += '&' + this.getVersion(version);
      } else {
        uri += '?' + this.getVersion(version);
      }
    }

    return uri;
  }

  getVersion(version) {
    if (version === true || !version) {
      if (!this.currentVersion) {
        this.currentVersion = crypto.randomBytes(12).toString('hex');
      }

      return this.currentVersion;
    }

    return version;
  }
}

export function terserTask() {
  return gulpTerser({
    module: true,
    mangle: true,
    toplevel: true,
  }, terser.minify).on('error', function (e) {
    console.error(
      '[ES syntax validate fail]',
      e.toString()
    );
    this.emit('end');
  });
}
