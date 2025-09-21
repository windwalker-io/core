import { webpackBundle } from '@windwalker-io/fusion';
import fs from 'fs';
import { globSync } from 'glob';
import path from 'path';
import { findModules } from './asset-sync.mjs';

/**
 *
 * @param {string} mainFile
 * @param {string|string[]} source
 * @param {string} dest
 * @param {any} options
 * @returns {Promise<any>}
 */
export async function bundleJS(
  mainFile = `./resources/assets/src/app.ts`,
  source = 'src/Module/**/*.ts',
  dest = 'www/assets/js/app/app.js',
  options = {}
) {
  const workingDir = process.cwd();

  if (typeof source === 'string') {
    source = [source];
  }

  const files = findFilesFromGlobArray([
    ...findModules('**/assets/*.ts'),
    ...source,
  ]);

  let listJS = "{\n";

  for (const file of files) {
    if (file.fullpath.endsWith('.d.ts')) {
      continue;
    }

    let key = file.relativePath.replace(/assets$/, '').toLowerCase();
    key = key.substring(0, key.lastIndexOf('.'));
    listJS += `'${key}': () => import('${file.fullpath}'),\n`;
  }

  listJS += "}";

  let ts = `
import loader from '@windwalker-io/core/src/loader/core-loader.ts';

loader.register(${listJS});

export default loader;
  `;

  // const base64 = Buffer.from(ts).toString('base64');
  // const dataUri = `data:text/javascript;base64,${base64}`;
  const tmpDir = workingDir + '/tmp/fusion';
  fs.mkdirSync(tmpDir, { recursive: true });

  const tmpFile = tmpDir + '/app.js';
  fs.writeFileSync(tmpFile, ts);

  const r = await webpackBundle(
    tmpFile,
    dest,
    (config) => {
      config.devtool = false;
      // config.entry = dataUri;
      // config.output.uniqueName = 'app';
      config.output.libraryTarget = 'module';
      config.experiments.outputModule = true;
      config.resolve.modules.push(path.resolve('./'));
      config.context = path.resolve('./');
      config.resolve.alias = {
        '@main': path.resolve(mainFile),
        '@app': path.resolve(mainFile),
      };
    }
  );

  // fs.unlinkSync(tmpFile);

  return r;
}

function findFilesFromGlobArray(sources) {
  let files = [];

  for (const source of sources) {
    files = [
      ...files,
      ...findFiles(source)
    ];
  }

  return files;
}

/**
 * @param {string} src
 */
function findFiles(src) {
  const i = src.lastIndexOf('**');

  const path = src.substring(0, i);

  return globSync(src).map((file) => {
    file = file.replace(/\\/g, '/');

    return {
      fullpath: file,
      relativePath: file.substring(path.length)
    };
  });
}
