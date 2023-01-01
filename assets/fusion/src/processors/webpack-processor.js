/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import concat from 'gulp-concat';
import eol from 'gulp-eol';
import { dest as toDest } from '../base/base.js';
import { merge } from '../utilities/utilities.js';
import { webpackBasicConfig } from '../utilities/webpack.js';
import JsProcessor from './js-processor.js';

let webpackStream;
let named;

try {
  named = (await import('vinyl-named-with-path')).default;
  webpackStream = (await import('webpack-stream')).default;
} catch (e) {
  const chalk = (await import('chalk')).default;
  console.error(chalk.red(e.message));
  console.error(
    `\nPlease run "${chalk.yellow('yarn add webpack webpack-stream webpack-comment-remover-loader ' +
    'vinyl-named-with-path babel-loader css-loader sass-loader style-loader postcss-loader')}" first.\n`
  );
  process.exit(255);
}

export default class WebpackProcessor extends JsProcessor {
  async prepareOptions(options) {
    options.webpack = await this.getWebpackConfig();

    return super.prepareOptions(options);
  }

  compile(dest, options) {
    if (options.override != null) {
      if (typeof options.override === 'function') {
        options.override(options.webpack);
      } else {
        options.webpack = options.override;
      }
    }

    if (options.merge != null) {
      options.webpack = merge(
        options.webpack,
        options.merge
      );
    }

    return this.pipe(named())
      .pipe(
        webpackStream(options.webpack)
          .on('error', () => {
            // Must force webpack stream end
            this.stream.emit('end');
          })
      );
  }

  doProcess(dest, options) {
    this.pipe(eol('\n'))
      .compile(dest, options)
      .pipeIf(dest.merge, () => concat(dest.file))
      .pipe(toDest(dest.path));
  }

  async getWebpackConfig() {
    return webpackBasicConfig();
  }
}
