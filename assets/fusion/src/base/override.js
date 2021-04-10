/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import gulp from 'gulp';
import { cliInput } from '../utilities/cli.js';
import { watching } from './watch.js';

const ranTasks = [];

export const series = gulp.series;
export const parallel = gulp.parallel;

gulp.series = decorate(series);
gulp.parallel = decorate(parallel);

function decorate(origin) {
  return function (tasks) {
    storeNewTasks(tasks);

    return origin(tasks);
  };
}

function storeNewTasks(tasks) {
  if (!Array.isArray(tasks)) {
    tasks = [tasks];
  }

  const newTasks = tasks.filter(task => !ranTasks.includes(task));

  ranTasks.push(...newTasks);

  return newTasks;
}

gulp.on('stop', () => {
  if (cliInput['watch'] && !watching.start) {
    watching.tasks.forEach((watchTask) => {
      if (watchTask) {
        gulp.watch(...watchTask.args);
      }
    });

    watching.start = true;
  }
});
