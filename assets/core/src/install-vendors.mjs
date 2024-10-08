

import { src, symlink, copy } from '@windwalker-io/fusion';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import { loadJson } from './utils.mjs';
import path from 'path';
import fs from 'fs';

export async function installVendors(npmVendors = [], legacyComposerVendors = [], to = 'www/assets/vendor') {
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

  const composerJsons = getInstalledComposerVendors()
    .map((cv) => `vendor/${cv}/composer.json`)
    .map((file) => loadJson(file))
    .filter((composerJson) => composerJson?.extra?.windwalker != null);

  // Install npm vendors
  vendors = findNpmVendors(composerJsons).concat(vendors);
  vendors = [...new Set(vendors)];

  vendors.forEach((vendor) => {
    if (fs.existsSync(`node_modules/${vendor}/`)) {
      console.log(`[${action} NPM] node_modules/${vendor}/ => ${root}/${vendor}/`);
      doInstall(`node_modules/${vendor}/`, `${root}/${vendor}/`);
    }
  });

  // Install composer packages assets
  composerJsons.forEach((composerJson) => {
    const vendorName = composerJson.name;

    let assets = composerJson?.extra?.windwalker?.assets?.link;

    if (!assets) {
      return;
    }

    if (!assets.endsWith('/')) {
      assets += '/';
    }

    if (fs.existsSync(`vendor/${vendorName}/${assets}`)) {
      console.log(`[${action} Composer] vendor/${vendorName}/${assets} => ${root}/${vendorName}/`);
      doInstall(`vendor/${vendorName}/${assets}`, `${root}/${vendorName}/`);
    }
  });

  // Install legacy packages assets
  legacyComposerVendors.forEach((vendorName) => {
    console.log(vendorName, fs.existsSync(`vendor/${vendorName}/assets`));
    if (fs.existsSync(`vendor/${vendorName}/assets`)) {
      console.log(`[${action} Composer] vendor/${vendorName}/assets/ => ${root}/${vendorName}/`);
      doInstall(`vendor/${vendorName}/assets/`, `${root}/${vendorName}/`);
    }
  });

  // Install local saved vendors
  const staticVendorDir = 'resources/assets/vendor/';

  if (fs.existsSync(staticVendorDir)) {
    const staticVendors = fs.readdirSync(staticVendorDir);

    for (const staticVendor of staticVendors) {
      if (staticVendor.startsWith('@')) {
        const subVendors = fs.readdirSync(staticVendorDir + staticVendor);

        for (const subVendor of subVendors) {
          const subVendorName = staticVendor + '/' + subVendor;
          console.log(`[${action} Local] resources/assets/vendor/${subVendorName}/ => ${root}/${subVendorName}/`);
          doInstall(staticVendorDir + subVendorName + '/', `${root}/${subVendorName}/`);
        }
      } else {
        console.log(`[${action} Local] resources/assets/vendor/${staticVendor}/ => ${root}/${staticVendor}/`);
        doInstall(staticVendorDir + staticVendor, `${root}/${staticVendor}/`);
      }
    }
  }
}

function doInstall(source, dest) {
  if (process.env.INSTALL_VENDOR === 'hard') {
    copy(source + '/**/*', dest);
  } else {
    src(source).pipe(symlink(dest));
  }
}

function findNpmVendors(composerJsons = []) {
  const pkg = path.resolve(process.cwd(), 'package.json');
  const pkgJson = loadJson(pkg);

  let vendors = Object.keys(pkgJson.devDependencies || {})
    .concat(Object.keys(pkgJson.dependencies || {}))
    .map(id => `node_modules/${id}/package.json`)
    .map((file) => loadJson(file))
    .filter(pkgJson => pkgJson?.windwalker != null)
    .map(pkgJson => pkgJson?.windwalker.vendors || [])
    .flat();

  const vendorsFromComposer = composerJsons
    .map((composerJson) => {
      return [
        ...composerJson?.extra?.windwalker?.asset_vendors || [],
        ...composerJson?.extra?.windwalker?.assets?.exposes || [],
        ...Object.keys(composerJson?.extra?.windwalker?.assets?.vendors || {})
      ]
    })
    .flat();

  return [ ...new Set(vendors.concat(vendorsFromComposer)) ];
}

function injectNpmPackages(composerVendors = []) {

}

function getInstalledComposerVendors() {
  const composerFile = path.resolve(process.cwd(), 'composer.json');
  const composerJson = loadJson(composerFile);

  return [
    ...new Set(
      Object.keys(composerJson['require'] || {})
        .concat(Object.keys(composerJson['require-dev'] || {}))
    )
  ];
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
