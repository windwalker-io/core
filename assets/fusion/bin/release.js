/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2018 Asikart.
 * @license    MIT
 */

import { cliInput } from '../src/utilities/cli.js';
import { execSync as exec } from 'child_process';

const args = cliInput._;

if (!args.length) {
  console.log('Please provide release type (major | minor | patch | premajor | preminor | prepatch | prerelease)');
  process.exit(255);
}

const help = `
Usage: release.js -- <arguments for "npm version">
  -b    Branch name to push. 
`;

if (cliInput['help'] || cliInput['h']) {
  console.log(help);
  process.exit(0);
}

console.log(`>>> npm version ${args.join(' ')}`);
const buffer = exec(`npm version ${args.join(' ')}`, { stdio: 'inherit' });

const ver = buffer.toString().split("\n")[1];

console.log('>>> Git commit all');
exec(`git add .`, { stdio: 'inherit' });
try {
  exec(`git commit -am "Prepare release @windwalker-io/fusion. ${ver}"`);
} catch (e) {
  console.log(e.message);
}

const branch = cliInput['b'] || 'master';

console.log('>>> Push to git', { stdio: 'inherit' });

exec(`git push origin ${branch}`);
// exec(`git checkout ${branch}`);

console.log('>> Publish to npm');

exec(`npm publish`, { stdio: 'inherit' });
