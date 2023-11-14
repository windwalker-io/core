import { findModules } from '@windwalker-io/core/src/index.mjs';
import { dest as toDest, src, ts } from '@windwalker-io/fusion';
import { postStream, prepareStream } from '@windwalker-io/fusion/src/lifecycles.js';
import { extractDest } from '@windwalker-io/fusion/src/utilities/utilities.js';
import { existsSync } from 'fs';
import rename from 'gulp-rename';
import path from 'path';

/**
 * @deprecated
 */
export function jsSync(source = 'src/Module', dest, options = {}) {
  const tsOptions = options.ts || {};

  if (!tsOptions.tsconfig) {
    tsOptions.tsconfig = path.resolve('tsconfig.json');

    if (!existsSync(tsOptions.tsconfig)) {
      tsOptions.tsconfig = path.resolve('node_modules/@windwalker-io/unicorn/tsconfig.js.json');
    }
  }

  const sourceList = [];

  sourceList.push(...findModules('**/assets/*.{js,mjs}'));
  sourceList.push(source + '**/assets/*.{js,mjs}');

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

  return Promise.all([
    new Promise((resolve) => {
      postStream(stream).on('end', (event) => {
        const data = {
          event,
          src,
          dest: jsDest,
          stream
        };

        resolve(data);
      });
    }),
    // Legacy mode
    ts(
      [
        ...findModules('**/assets/*.ts'),
        'node_modules/@windwalker-io/unicorn/src/types/*.d.ts',
        `${source}/**/*.ts`,
      ],
      dest,
      {
        rename: (path) => {
          path.dirname = path.dirname.replace(/assets$/, '').toLowerCase();
        },
        ...tsOptions
      }
    )
  ]).then((v) => {
    return v[0];
  });
}
