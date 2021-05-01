/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import resolve from '@rollup/plugin-node-resolve';
import { babel } from '@rollup/plugin-babel';
import sourcemaps from 'rollup-plugin-sourcemaps';
import { babelBasicOptions } from './babel.js';

export async function rollupBasicConfig() {
  const babelOptions = babelBasicOptions().get();

  babelOptions.exclude = 'node_modules/**' // only transpile our source code
  babelOptions.babelHelpers = 'bundled';

  return {
    output: {
      format: 'iife',
      sourcemap: true,
    },
    plugins: [
      resolve(),
      babel(babelOptions),
      sourcemaps()
    ],
  };
}
