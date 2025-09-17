import path from 'path';
import { loadJson } from './fs';

export function findModules(suffix = ''): string[] {
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

