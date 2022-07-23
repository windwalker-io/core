/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import del from 'del';
import fusion, { dest, series, src, waitAllEnded, watch } from '../src/index.js';
import { parallel } from '../src/index.js';
import { babelEmptyOptions, BabelOptions } from '../src/utilities/babel.js';

css.description = 'Build CSS';
export default async function css() {
  watch(['./src/css/**/*.css']);

  return waitAllEnded(
    fusion.css('./src/css/**/*.css', './dest/css/moved/'),
    fusion.css('./src/css/**/*.css', './dest/css/renamed.css'),
    fusion.sass('./src/scss/**/*.scss', './dest/css/scss/'),
  );
}

export async function js() {
  fusion.watch(['./src/js/**/*.js']);

  fusion.js('./src/js/**/*.js', './dest/js/simple/');
  fusion.js('./src/js/**/*.js', './dest/js/simple/merged.js', { minify: 'same_file' });
  fusion.js(
    ['./src/js/foo.js', './src/js/bar.js'],
    './dest/js/simple/merged2.js'
  );
  // sass('./src/scss/**/*.scss', './dest/css/scss/');
}

export async function jsProd() {
  fusion.watch(['./src/js/**/*.js']);

  fusion.js('./src/js/bar.js', './dest/js/prod/bar.js');
}

export async function babel() {
  fusion.watch(['./src/js/**/*.js']);

  return waitAllEnded(
    fusion.babel('./src/js/**/*.js', './dest/js/babel/', { module: 'systemjs'})
  );
}

export async function module() {
  fusion.watch(['./src/js/**/*.js']);

  fusion.module('./src/module/**/*.js', './dest/module/', { es5: './dest/no-module' });
}

export async function webpack() {
  fusion.watch(['./src/webpack/**/*.js']);

  fusion.webpack('./src/webpack/index.js', './dest/webpack/webpack-dest.js');
}

export async function rollup() {
  fusion.watch(['./src/webpack/**/*.js']);

  fusion.rollup('./src/webpack/index.js', './dest/rollup/rollup-dest.js');
}

export async function vue() {
  fusion.watch(['./src/vue/**/*.js']);

  return waitAllEnded(
    fusion.vue('./src/vue/main.ts', './dest/vue/vue-dest.js'),
    fusion.vue('./src/vue/entries/*.ts', './dest/vue/pages/', { root: './src/vue' })
  );
}

export async function ts() {
  fusion.watch(['./src/ts/**/*.css']);

  fusion.ts('./src/ts/**/*.ts', './dest/ts/', { ts: { target: 'es6' }});
}

export const wa = async () => [
  fusion.watch('./src/css/**/*.css'),
  src('./src/css/**/*.css').pipe(dest('./dest/css/'))
];

export function clean() {
  return del(
    [
      './dest/**/*',
      '!.gitkeep'
    ]
  );
}

export const all = series(clean, css);


