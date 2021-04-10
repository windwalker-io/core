/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { cliInput } from '../src/utilities/cli.js';
import notifier from 'node-notifier';

const m = cliInput._[0];

const options = JSON.parse(Buffer.from(m, 'base64').toString('ascii'));

notifier.notify(options);
