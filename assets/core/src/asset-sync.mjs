/**
 * Part of funclass project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    __LICENSE__
 */

import { dest as toDest, src } from '@windwalker-io/fusion';
import { postStream, prepareStream } from '@windwalker-io/fusion/src/lifecycles.js';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import { loadJson } from './utils.mjs';
import rename from 'gulp-rename';
import path from 'path';

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
