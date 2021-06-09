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

console.log('>>> Git commit all');
exec(`git add .`);
try {
  exec(`git commit -am "Prepare release."`);
} catch (e) {
  console.log(e.message);
}

console.log(`>>> npm version ${args.join(' ')}`);
exec(`npm version ${args.join(' ')}`);

const branch = cliInput['b'] || 'master';

// console.log('>>> Push to git');
//
// exec(`git push origin ${branch}`);
// exec(`git push origin --tags -f`);
// exec(`git checkout master`);

console.log('>> Publish to npm');

exec(`npm publish`);
