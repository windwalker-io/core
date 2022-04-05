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
  const action = process.env.INSTALL_VENDOR === 'hard' ? 'Copy' : 'Link';

  if (!fs.existsSync(root)) {
    fs.mkdirSync(root);
  }

  const dirs = fs.readdirSync(root, { withFileTypes: true })
    .filter(d => d.isDirectory())
    .map(dir => path.join(root, dir.name));

  dirs.unshift(root);

  dirs.forEach((dir) => {
    deleteExists(dir);
  });

  vendors = findVendors().concat(vendors);
  vendors = [...new Set(vendors)];

  vendors.forEach((vendor) => {
    if (fs.existsSync(`node_modules/${vendor}/`)) {
      console.log(`[${action} NPM] node_modules/${vendor}/ => ${root}/${vendor}/`);
      doInstall(`node_modules/${vendor}/`, `${root}/${vendor}/`);
    }
  });

  composerVendors.forEach((vendor) => {
    if (fs.existsSync(`vendor/${vendor}/assets`)) {
      console.log(`[${action} Composer] vendor/${vendor}/assets => ${root}/${vendor}/`);
      doInstall(`vendor/${vendor}/assets/`, `${root}/${vendor}/`);
    }
  });

  console.log(`[${action} Local] resources/assets/vendor/**/* => ${root}/`);
  doInstall('resources/assets/vendor/*', `${root}/`);
}

function doInstall(source, dest) {
  if (process.env.INSTALL_VENDOR === 'hard') {
    copy(source + '/**/*', dest);
  } else {
    src(source).pipe(symlink(dest));
  }
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

function deleteExists(dir) {
  if (!fs.existsSync(dir)) {
    return;
  }

  const subDirs = fs.readdirSync(dir, { withFileTypes: true });

  subDirs.forEach((subDir) => {
    if (subDir.isSymbolicLink() || subDir.isFile()) {
      fs.unlinkSync(path.join(dir, subDir.name));
    } else if (subDir.isDirectory()) {
      deleteExists(path.join(dir, subDir.name));
    }
  });

  fs.rmdirSync(dir);
}
