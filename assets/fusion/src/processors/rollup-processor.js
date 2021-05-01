/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import concat from 'gulp-concat';
import eol from 'gulp-eol';
import { dest as toDest, src } from '../base/base.js';
import { prepareStream } from '../lifecycles.js';
import { rollupBasicConfig } from '../utilities/rollup.js';
import { merge } from '../utilities/utilities.js';
import JsProcessor from './js-processor.js';
import path from 'path';

let rollupStream;
let source;

try {
  rollupStream = (await import('@rollup/stream')).default;
  source = (await import('vinyl-source-stream')).default;
} catch (e) {
  const chalk = (await import('chalk')).default;
  console.error(chalk.red(e.message));
  console.error(`\nPlease run "${chalk.yellow('yarn add @rollup/stream @rollup/plugin-node-resolve ' +
    '@rollup/plugin-babel rollup-plugin-sourcemaps vinyl-source-stream')}" first.\n`);
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

    if (this.source.indexOf('*') !== -1) {
      throw new Error('rollup processor currently not support wildcards.');
    }

    options.rollup.input = this.source;
    options.rollup.output.file = dest.path + '/target.js';

    this.stream = prepareStream(rollupStream(options.rollup))
      .pipe(source(dest.file || path.basename(this.source)));

    return this;
  }

  doProcess(dest, options) {
    this.pipe(eol('\n'))
      .compile(dest, options)
      // .pipeIf(dest.merge, () => concat(dest.file))
      .pipe(toDest(dest.path));
  }

  async getRollupConfig() {
    return rollupBasicConfig();
  }
}
