/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import errorHandler from 'gulp-error-handle';
import { debounce } from 'lodash-es';
import { config } from './config.js';
import { cliInput } from './utilities/cli.js';
import path from 'path';
import { notify } from './utilities/notifier.js';
import livereload from 'gulp-livereload';

export function prepareStream(stream) {
  stream = stream
    .pipe(errorHandler((e) => {
      config.notifySuccess = false;

      notify({
        title: 'Windwalker Fusion',
        message: '[Something Error] Please see terminal to know more information.',
        icon: path.resolve() + '/../resources/img/error.png'
      });

      // notifier.notify({
      //   title: 'Windwalker Fusion',
      //   message: '[Something Error] Please see terminal to know more information.',
      //   icon: path.resolve() + '/../resources/img/error.png'
      // });
    }))
    .on('end', e => {
      postTask();
    })
    .on('finish', e => {
      //
    });

  return stream;
}

export function postStream(stream) {
  if (cliInput['livereload']) {
    stream = stream.pipe(livereload(config.livereload));
  }

  return stream;
}

export function postTask() {
  notifySuccess();
}

const notifySuccess = debounce(() => {
  // if (config.notifySuccess) {
  notify({
    title: 'Windwalker Fusion',
    message: 'Build success',
    icon: path.resolve() + '/../resources/img/windwalker.png',
    wait: false
  });
  // }

  // config.notifySuccess = true;

  // if (startWatching === false) {
  //   startWatching = true;
  //
  //   if (input['livereload']) {
  //     const livereload = require('gulp-livereload');
  //
  //     livereload.listen(config.livereload);
  //   }
  //
  //   for (let watch of this.watches) {
  //     gulp.watch(watch.glob, [watch.task]);
  //   }
  // }
}, 300);

export function waitAllEnded(...promises) {
  const waitQueue = [];

  promises.forEach((promise) => {
    waitQueue.push(new Promise((resolve) => {
      promise.then((stream) => {
        stream.on('end', resolve);
      });
    }));
  });

  return Promise.all(waitQueue);
}

export function waitFirstEnded(...promises) {
  const waitQueue = [];

  promises.forEach((promise) => {
    waitQueue.push(new Promise((resolve) => {
      promise.then((stream) => {
        stream.on('end', resolve);
      });
    }));
  });

  return Promise.race(waitQueue);
}
