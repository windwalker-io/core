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
import { terser } from 'rollup-plugin-terser';

export async function rollupBasicConfig() {
  const babelOptions = babelBasicOptions().get();

  babelOptions.exclude = 'node_modules/**' // only transpile our source code
  babelOptions.babelHelpers = 'bundled';

  return {
    output: {
      format: 'iife',
      sourcemap: process.env.NODE_ENV !== 'production',
    },
    plugins: [
      resolve(),
      babel(babelOptions),
      sourcemaps(),
      process.env.NODE_ENV === 'production'
        ? terser({
          module: true,
          mangle: true,
          toplevel: true,
          output: {
            comments: false
          }
        })
        : null
    ],
  };
}
