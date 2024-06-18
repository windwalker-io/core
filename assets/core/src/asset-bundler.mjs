import { webpackBundle } from '@windwalker-io/fusion';
import { globSync } from 'glob';
import path from 'path';
import { findModules } from './asset-sync.mjs';

export async function bundleJS(source = 'src/Module', dest = 'www/assets/js/', options = {}) {
  const mainFile = `./resources/assets/src/front/main.ts`;

  const files = findFilesFromGlobArray([
    ...findModules('**/assets/*.ts'),
    `${source}/**/*.ts`,
  ]);

  let listJS = "{\n";

  for (const file of files) {
    let key = file.relativePath.replace(/assets$/, '').toLowerCase();
    key = key.substring(0, key.lastIndexOf('.'));
    listJS += `'${key}': () => import('./${file.fullpath}'),\n`;
  }

  listJS += "}";

  let ts = `
import loader from '@windwalker-io/core/src/loader/core-loader.ts';

loader.register(${listJS});

export default loader;
  `;

  const base64 = Buffer.from(ts).toString('base64');
  const dataUri = `data:text/javascript;base64,${base64}`;

  return webpackBundle(
    '',
    `${dest}/app.js`,
    (config) => {
      config.devtool = false;
      config.entry = dataUri;
      config.output.uniqueName = 'app';
      config.output.libraryTarget = 'module';
      config.experiments.outputModule = true;
      config.resolve.alias = {
        '@main': path.resolve(mainFile)
      };
    }
  );
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
    return {
      fullpath: file,
      relativePath: file.substring(path.length)
    };
  });
}
