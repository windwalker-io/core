/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { babelBasicOptions } from '../utilities/babel.js';
import { logError } from '../utilities/error.js';
import filter from 'gulp-filter';
import rename from 'gulp-rename';
import { dest as toDest } from '../base/base.js';
import BabelProcessor from './babel-processor.js';
import path from 'path';

const gulpBabel = (await import('gulp-babel')).default;

export default class ModuleProcessor extends BabelProcessor {
  async prepareOptions(options) {
    // options.targets = options.targets || 'last 1 version';

    options = await super.prepareOptions(options);

    options.babel.options.presets[0] = ['modern-browsers', { modules: false }];

    return options;
  }

  doProcess(dest, options) {
    super.doProcess(dest, options);

    this.pipeIf(options.es5, () => {
      const babelOptions = babelBasicOptions();
      options.module = options.module || 'systemjs';

      switch (options.module) {
        case 'umd':
          babelOptions.addPlugin('@babel/plugin-transform-modules-umd');
          break;

        case 'amd':
          babelOptions.addPlugin('@babel/plugin-transform-modules-amd');
          break;

        case 'systemjs':
        case true:
          babelOptions.addPlugin('@babel/plugin-transform-modules-systemjs');
          break;
      }

      // delete options.babel.options.presets[0][1].modules;

      const path = options.es5 === true ? path.join(dest.path, 'es5') : options.es5;

      this.pipe(filter('**/*.js'))
        .pipe(gulpBabel(babelOptions.get()).on('error', logError(e => console.log(e.codeFrame))))
        .pipe(toDest(path));
    });
  }
}
