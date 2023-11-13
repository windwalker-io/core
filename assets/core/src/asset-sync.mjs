/**
 * Part of funclass project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    __LICENSE__
 */

import { babel, dest as toDest, src, ts, wait } from '@windwalker-io/fusion';
import { postStream, prepareStream } from '@windwalker-io/fusion/src/lifecycles.js';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import { loadJson } from './utils.mjs';
import rename from 'gulp-rename';
import path from 'path';

/**
 * @deprecated
 */
export function jsSync(source = 'src/Module', dest) {
  // const root = source + '/**/assets/';

  // glob(root, {}, function (err, dirs) {
  //   for (let dir of dirs) {
  //     glob(dir + '/**/*.{js,mjs}', { absolute: false }, (erro, files) => {
  //       console.log(files);
  //
  //       for (let file of files) {
  //         console.log(
  //           path.relative(dir, file)
  //         );
  //       }
  //     });
  //   }
  // });

  const sourceList = [];

  sourceList.push(...findModules('**/assets/*.{js,mjs,ts}'));
  sourceList.push(source + '**/assets/*.{js,mjs,ts}');

  let stream = prepareStream(src(sourceList));

  stream = stream.pipe(rename((path) => {
    path.dirname = path.dirname.replace(/assets$/, '').toLowerCase();
  }));

  const jsDest = extractDest(dest);

  //
  // // if (dest.merge) {
  // //   stream = stream.pipe(rename(path.basename(dest.file)));
  // // }
  //
  stream = stream.pipe(toDest(jsDest.path).on('error', e => console.error(e)));

  return new Promise((resolve) => {
    postStream(stream).on('end', (event) => {
      const data = {
        event,
        src,
        dest: jsDest,
        stream
      };

      resolve(data);
    });
  });
}

export function syncModuleScripts(source = 'src/Module', dest = 'www/assets/js/view/', options = {}) {
  const jsOptions = options.js || {};
  const tsOptions = options.ts || {};

  return [
    syncModuleJS(source, dest, jsOptions),
    syncModuleTS(source, dest, tsOptions),
  ];
}

export function syncModuleJS(source = 'src/Module', dest = 'www/assets/js/view/', options = {}) {
  return babel(
    [
      `${source}/**/*.{js,mjs}`,
      ...findModules('**/assets/*.{js,mjs}')
    ],
    dest,
    {
      module: 'systemjs',
      rename: (path) => {
        path.dirname = path.dirname.replace(/assets$/, '').toLowerCase();
      },
      ...options
    }
  );
}

export function syncModuleTS(source = 'src/Module', dest = 'www/assets/js/view/', options = {}) {
  return ts(
    [
      `${source}/**/*.ts`,
      // Todo: Research if tsconfig.json can replace this line
      'resources/assets/src/**/*.d.ts',
      ...findModules('**/assets/*.ts')
    ],
    dest,
    {
      tsconfig: path.resolve('tsconfig.json'),
      rename: (path) => {
        path.dirname = path.dirname.replace(/assets$/, '').toLowerCase();
      },
      ...options
    }
  );
}

export function findModules(suffix = '') {
  const pkg = path.resolve(process.cwd(), 'composer.json');

  const pkgJson = loadJson(pkg);

  const vendors = Object.keys(pkgJson['require'] || {})
    .concat(Object.keys(pkgJson['require-dev'] || {}))
    .map(id => `vendor/${id}/composer.json`)
    .map((file) => loadJson(file))
    .filter(pkgJson => pkgJson?.extra?.windwalker != null)
    .map(pkgJson => {
      return pkgJson?.extra?.windwalker?.modules?.map((module) => {
        return `vendor/${pkgJson.name}/${module}/${suffix}`;
      }) || [];
    })
    .flat();

  return [ ...new Set(vendors) ];
}
