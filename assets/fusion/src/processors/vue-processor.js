/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { clone } from 'lodash-es';
import { webpackVue3Config } from '../utilities/webpack.js';
import WebpackProcessor from './webpack-processor.js';
import path from 'path';
import fs from 'fs';

export default class VueProcessor extends WebpackProcessor {
  async prepareOptions(options) {
    options = await super.prepareOptions(options);

    if (options.excludeVue) {
      options.webpack.externals = { vue: 'Vue' };
    }

    return options;
  }

  compile(dest, options) {
    let src = options.root;

    if (!src) {
      src = this.source;

      if (typeof src === 'string') {
        const i = src.indexOf('*');

        if (i !== -1) {
          src = src.substr(0, i);
        } else if (fs.lstatSync(src).isFile()) {
          src = path.dirname(src);
        }
      }
    }

    if (src && fs.statSync(src).isDirectory()) {
      options.webpack.resolve.alias['@'] = path.resolve(src);
    }

    return super.compile(dest, options);
  }

  async getWebpackConfig() {
    return await webpackVue3Config();
  }
}
