
import { babel, ts } from '@windwalker-io/fusion';
import path from 'path';
import { loadJson } from './utils.mjs';
export * from './legacy/4.0/js-sync.mjs';

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
      ...findModules('**/assets/*.{js,mjs}'),
      `${source}/**/*.{js,mjs}`,
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
      // Todo: Research if tsconfig.json can replace this line
      'resources/assets/src/**/*.d.ts',
      ...findModules('**/assets/*.ts'),
      `${source}/**/*.ts`,
    ],
    dest,
    {
      tsconfig: path.resolve('tsconfig.json'),
      rename: (path) => {
        path.dirname = path.dirname.replace(/\/assets/, '').toLowerCase();
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

  return [...new Set(vendors)];
}
