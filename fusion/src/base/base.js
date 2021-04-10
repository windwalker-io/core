/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import gulp from 'gulp';

export const src = gulp.src;
export const dest = gulp.dest;
export const symlink = gulp.symlink;
export const lastRun = gulp.lastRun;
export const task = gulp.task;
export const registry = gulp.registry;
export const tree = gulp.tree;

export * from './watch.js';
export * from './copy.js';
export * from './livereload.js';
export * from './override.js';
