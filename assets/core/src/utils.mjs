/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import fs from 'fs';

export function loadJson(file) {
  if (!fs.existsSync(file)) {
    return null;
  }

  return JSON.parse(fs.readFileSync(file));
}
