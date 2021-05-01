/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import concat from 'gulp-concat';
import eol from 'gulp-eol';
import { dest as toDest } from '../base/base.js';
import { rollupBasicConfig } from '../utilities/rollup.js';
import { merge } from '../utilities/utilities.js';
import JsProcessor from './js-processor.js';

let gulpRollup;

try {
  gulpRollup = (await import('gulp-rollup')).default;
} catch (e) {
  const chalk = (await import('chalk')).default;
  console.error(chalk.red(e.message));
  console.error(`\nPlease run "${chalk.yellow('yarn add rollup gulp-rollup @rollup/plugin-node-resolve @rollup/plugin-babel')}" first.\n`);
  process.exit(255);
}

export default class RollupProcessor extends JsProcessor {
  async prepareOptions(options) {
    options.rollup = await this.getRollupConfig();

    return super.prepareOptions(options);
  }

  compile(dest, options) {
    if (options.override != null) {
      if (typeof options.override === 'function') {
        options.override(options.rollup);
      } else {
        options.rollup = options.override;
      }
    }

    if (options.merge != null) {
      options.rollup = merge(
        options.rollup,
        options.merge
      );
    }

    return this.pipe(
        gulpRollup({
          rollup: options.rollup,
          input: './src/webpack/index.js'
        })
      );
  }

  doProcess(dest, options) {
    this.pipe(eol('\n'))
      .compile(dest, options)
      .pipeIf(dest.merge, () => concat(dest.file))
      .pipe(toDest(dest.path));
  }

  async getRollupConfig() {
    return rollupBasicConfig();
  }
}
