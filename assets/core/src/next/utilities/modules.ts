import { createRequire } from 'node:module';

export function resolveModuleRealpath(url: string, module: string) {
  const require = createRequire(url);

  return  require.resolve(module);
}

