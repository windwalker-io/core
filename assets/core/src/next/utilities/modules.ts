import { createRequire } from 'node:module';

export function resolveModuleRealpath(url: string, module: string) {
  const require = createRequire(url);

  return  require.resolve(module);
}

export function stripUrlQuery(src: string) {
  const qPos = src.indexOf('?');

  if (qPos !== -1) {
    return src.substring(0, qPos);
  }

  return src;
}
