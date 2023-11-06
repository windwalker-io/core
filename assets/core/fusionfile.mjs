/**
 * Part of Windwalker Fusion project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

import fusion, { watch, parallel, src, dest, wait, webpackVueBundle } from '@windwalker-io/fusion';
import { babelBasicOptions } from '@windwalker-io/fusion/src/utilities/babel.js';
import postcss from 'gulp-postcss';
import path from 'path';
import tailwindcss from 'tailwindcss';
import { BundleAnalyzerPlugin } from 'webpack-bundle-analyzer';

export async function debuggers() {
  return webpackVueBundle(
    'src/debugger/debugger.js',
    'dist/debugger/index.js',
    (config) => {
      config.resolve.alias = {
        '@': path.resolve(path.resolve(), './src/debugger/') // Will be overwrite when compile
      };

      // @see https://webpack.js.org/guides/public-path/#automatic-publicpath
      config.output.publicPath = "auto";

      // config.plugins.push(
      //   new BundleAnalyzerPlugin()
      // );
    }
  );
}

export async function console() {
  // Watch start
  watch(['scss/**/*.scss', 'src/console/debugger-console.js', '../../views/debugger/**/*.blade.php']);
  // Watch end

  // fusion.sass('scss/debugger.scss', 'dist/debugger.css');
  fusion.sass(
    'scss/debugger-console.scss',
    'dist/debugger-console.css',
    {
      postcss: [
        tailwindcss({ config: './tailwind/console.tailwind.config.cjs' })
      ]
    }
  );
  fusion.babel(
    'src/console/debugger-console.js',
    'dist/debugger-console.js'
  );

  // src('scss/debugger-console.css')
  //   .pipe(postcss([
  //     tailwindcss()
  //   ]))
  //   .pipe(dest('dist/'));
}

export default parallel(debuggers, console);

/*
 * APIs
 *
 * Compile entry:
 * fusion.js(source, dest, options = {})
 * fusion.babel(source, dest, options = {})
 * fusion.module(source, dest, options = {})
 * fusion.ts(source, dest, options = {})
 * fusion.typeScript(source, dest, options = {})
 * fusion.css(source, dest, options = {})
 * fusion.sass(source, dest, options = {})
 * fusion.copy(source, dest, options = {})
 *
 * Live Reload:
 * fusion.livereload(source, dest, options = {})
 * fusion.reload(file)
 *
 * Gulp proxy:
 * fusion.src(source, options)
 * fusion.dest(path, options)
 * fusion.watch(glob, opt, fn)
 * fusion.symlink(directory, options = {})
 * fusion.lastRun(task, precision)
 * fusion.tree(options = {})
 * fusion.series(...tasks)
 * fusion.parallel(...tasks)
 *
 * Stream Helper:
 * fusion.through(handler) // Same as through2.obj()
 *
 * Config:
 * fusion.disableNotification()
 * fusion.enableNotification()
 */
