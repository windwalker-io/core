/**
 * Part of Windwalker Fusion project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

import fusion, { watch, parallel, src, dest } from '@windwalker-io/fusion';
import { babelBasicOptions } from '@windwalker-io/fusion/src/utilities/babel.js';
import postcss from 'gulp-postcss';
import tailwindcss from 'tailwindcss';

export async function debuggers() {
  // Watch start
  watch(['src/debugger/**/*.{js,vue}', 'scss/**/*.scss']);
  // Watch end

  fusion.vue(
    'src/debugger/debugger.js',
    'dist/debugger/',
    {
      override: (config) => {
        // @see https://webpack.js.org/guides/public-path/#automatic-publicpath
        config.output.publicPath = "auto";
      }
    }
  );
  fusion.copy('images/**/*', 'dist/images/');
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
