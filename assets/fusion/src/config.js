/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

export const config = {
  public: '.',
  notify: true,
  notifySuccess: true,
  livereload: {
    start: true,
    host: null,
    port: null
  }
};

export const MinifyOption = {
  NONE: 'none',
  SAME_FILE: 'same_file',
  SEPARATE_FILE: 'separate_file',
  DEFAULT: null
};

MinifyOption.DEFAULT = process.env.NODE_ENV?.trim() === 'production'
  ? MinifyOption.SAME_FILE
  : MinifyOption.NONE;
