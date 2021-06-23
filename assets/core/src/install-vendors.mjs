/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import { src, symlink, copy } from '@windwalker-io/fusion';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import path from 'path';
import fs from 'fs';

export async function installVendors(vendors) {
  const root = 'www/assets/vendor';

  if (!fs.existsSync(root)) {
    fs.mkdirSync(root);
  }

  const dirs = fs.readdirSync(root, { withFileTypes: true })
    .filter(d => d.isDirectory())
    .map(dir => path.join(root, dir.name));

  dirs.unshift(root);

  dirs.forEach((dir) => {
    deleteLinks(dir);
  });

  vendors = findVendors().concat(vendors);
  vendors = [...new Set(vendors)];

  vendors.forEach((vendor) => {
    if (fs.existsSync(`node_modules/${vendor}/`)) {
      console.log(`[Link] node_modules/${vendor}/ => www/assets/vendor/${vendor}/`);
      src(`node_modules/${vendor}/`).pipe(symlink(`www/assets/vendor/${vendor}`));
    }
  });

  console.log('[Link] resources/assets/vendor/**/* => www/assets/vendor/');
  src('resources/assets/vendor/*').pipe(symlink('www/assets/vendor/'));
}

function findVendors() {
  const pkg = path.resolve(process.cwd(), 'package.json');

  const pkgJson = loadJson(pkg);

  const vendors = Object.keys(pkgJson.devDependencies || {})
    .concat(Object.keys(pkgJson.dependencies || {}))
    .map(id => `node_modules/${id}/package.json`)
    .map((file) => loadJson(file))
    .filter(pkgJson => pkgJson.windwalker != null)
    .map(pkgJson => pkgJson.windwalker.vendors || [])
    .flat();

  return [ ...new Set(vendors) ];
}

function loadJson(file) {
  if (!fs.existsSync(file)) {
    return null;
  }

  return JSON.parse(fs.readFileSync(file));
}

function deleteLinks(dir) {
  const links = fs.readdirSync(dir, { withFileTypes: true })
    .filter(d => d.isSymbolicLink());

  links.forEach((link) => {
    fs.unlink(path.join(dir, link.name), () => {});
  });

  fs.rmdir(dir, () => {});
}
