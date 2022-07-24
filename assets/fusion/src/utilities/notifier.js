/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import { debounce } from 'lodash-es';

export const notify = debounce((options = {}) => {
  const __filename = fileURLToPath(import.meta.url);
  const __dirname = path.dirname(__filename);

  // Detach notify from Windows wait blocking,
  // see https://github.com/mikaelbr/node-notifier/issues/311
  const child = spawn(
    'node',
    [
      path.join(__dirname, '/../../bin/notify.js'),
      `"${Buffer.from(JSON.stringify(options)).toString('base64')}"`
    ],
    {
      detached: true,
      windowsHide: true,
      // Force detach from stdio, see https://stackoverflow.com/a/12871847/8134785
      stdio: ['ignore', 'ignore', 'inherit']
    }
  );

  child.unref();
}, 150);
