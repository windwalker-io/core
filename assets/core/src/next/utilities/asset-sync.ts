import path, { resolve } from 'node:path';
import { loadJson } from './fs';

export function findModules(suffix = '', rootModule: string | null = 'src/Module'): string[] {
  const pkg = path.resolve(process.cwd(), 'composer.json');

  const pkgJson = loadJson(pkg);

  const vendors = Object.keys(pkgJson['require'] || {})
    .concat(Object.keys(pkgJson['require-dev'] || {}))
    .map(id => `vendor/${id}/composer.json`)
    .map((file) => loadJson(file))
    .filter(pkgJson => pkgJson?.extra?.windwalker != null)
    .map(pkgJson => {
      return pkgJson?.extra?.windwalker?.modules?.map((module: string) => {
        return `vendor/${pkgJson.name}/${module}/${suffix}`;
      }) || [];
    })
    .flat();

  if (rootModule) {
    vendors.unshift(rootModule + '/' + suffix);
  }

  return [...new Set(vendors)];
}

export function findPackages(suffix = '', withRoot = true): string[] {
  const pkg = path.resolve(process.cwd(), 'composer.json');

  const pkgJson = loadJson(pkg);

  const vendors = Object.keys(pkgJson['require'] || {})
    .concat(Object.keys(pkgJson['require-dev'] || {}))
    .map(id => `vendor/${id}/composer.json`)
    .map((file) => loadJson(file))
    .filter((pkgJson) => pkgJson?.extra?.windwalker != null)
    .map((pkgJson) => `vendor/${pkgJson.name}/${suffix}`)
    .flat();

  if (withRoot) {
    vendors.unshift(suffix);
  }

  return [...new Set(vendors)];
}

