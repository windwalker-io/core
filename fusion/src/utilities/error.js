/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import PluginError from 'plugin-error';
import fs from 'fs';

export function logError(handler = null) {
  return function (error) {
    console.error(error.toString());

    if (handler) {
      handler(error);
    }

    this.emit('end');
  };
}

export function showError(name, message) {
  process.stderr.write(new PluginError(name, message).toString() + '\n');
}

export function showSourceCode(file, line, column, color = true) {
  let source = fs.readFileSync(file, 'utf-8');

  let lines = source.split(/\r?\n/);
  let start = Math.max(line - 3, 0);
  let end = Math.min(line + 2, lines.length);

  let maxWidth = String(end).length;

  function mark(text) {
    if (color && chalk.red) {
      return chalk.red.bold(text);
    } else {
      return text;
    }
  }

  function aside(text) {
    if (color && chalk.gray) {
      return chalk.gray(text);
    } else {
      return text;
    }
  }

  return lines.slice(start, end).map(function(lineText, index) {
    let number = start + 1 + index;

    let gutter = ' ' + (' ' + number).slice(-maxWidth) + ' | ';
    if (number === line) {
      let spacing = aside(gutter.replace(/\d/g, ' ')) + lineText.slice(0, column - 1).replace(/[^\t]/g, ' ');
      return mark('>') + aside(gutter) + lineText + '\n ' + spacing + mark('^');
    } else {
      return ' ' + aside(gutter) + lineText;
    }
  }).join('\n');
}
