/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import gulp from 'gulp';
import { cliInput } from '../utilities/cli.js';
import EventEmitter from 'events';

export const watching = {
  tasks: [],
  start: false
};

export function watch(glob, opt, fn) {
  if (
    (typeof opt === 'object' && fn == null)
      || (opt == null && fn == null)
  ) {
    if (cliInput['watch'] && !watching.start) {
      const task = findCurrentTask(new Error());

      const fn = gulp._registry._tasks[task];

      if (!fn) {
        throw new Error(`Unable to find task: "${task}" from gulp registry.`);
      }

      watching.tasks.push({ task, args: [ glob, opt, fn ] });
    }

    return new EventEmitter();
  }

  return gulp.watch(glob, opt, fn);
}

/**
 * @param {Error} e
 * @returns {string}
 */
function findCurrentTask(e) {
  const FUNC_REGEX = /at\s{1}(?<func>[\w\.]+)\s{1}\([\W\w]+?\)/g;

  // Drop first and second
  FUNC_REGEX.exec(e.stack);
  let previous = FUNC_REGEX.exec(e.stack);

  // Get third
  let match = FUNC_REGEX.exec(e.stack);

  if ('bound' === match[1]) {
    match = previous;
  }

  return match[1];
}
