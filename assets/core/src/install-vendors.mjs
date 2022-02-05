/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import { src, symlink, copy } from '@windwalker-io/fusion';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import { loadJson } from './utils.mjs';
import path from 'path';
import fs from 'fs';

export async function installVendors(npmVendors, composerVendors = [], to = 'www/assets/vendor') {
  const root = to;
  let vendors = npmVendors;

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
      console.log(`[Link NPM] node_modules/${vendor}/ => ${root}/${vendor}/`);
      src(`node_modules/${vendor}/`).pipe(symlink(`${root}/${vendor}`));
    }
  });

  composerVendors.forEach((vendor) => {
    if (fs.existsSync(`vendor/${vendor}/assets`)) {
      console.log(`[Link Composer] vendor/${vendor}/assets => ${root}/${vendor}/`);
      src(`vendor/${vendor}/assets/`).pipe(symlink(`${root}/${vendor}/`));
    }
  });

  console.log(`[Link Local] resources/assets/vendor/**/* => ${root}/`);
  src('resources/assets/vendor/*').pipe(symlink(`${root}/`));
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

function deleteLinks(dir) {
  const links = fs.readdirSync(dir, { withFileTypes: true })
    .filter(d => d.isSymbolicLink());

  links.forEach((link) => {
    fs.unlink(path.join(dir, link.name), () => {});
  });

  fs.rmdir(dir, () => {});
}
