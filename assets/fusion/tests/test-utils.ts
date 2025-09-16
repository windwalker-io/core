import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { rimrafSync } from 'rimraf';
import { defineConfig, mergeConfig, UserConfig, build, createBuilder, InlineConfig } from 'vite';
import { useFusion } from '../dist';
import { FusionVitePluginUnresolved, FusionVitePluginOptions } from '../src/types';
import { show } from '../src/utilities/utilities';

export function clearDest() {
  rimrafSync(resolve(urlToDirname(import.meta.url), './dest'));
}

export function urlToFilename(url: string) {
  return fileURLToPath(url);
}

export function urlToDirname(url: string) {
  return dirname(fileURLToPath(url));
}

export function importFusionfile() {
  return () => import('./fusionfile');
}

export async function viteBuild(options: FusionVitePluginUnresolved, tasks?: string | string[], viteConfig: InlineConfig = {}) {
  const config = createViteConfig(options, tasks, viteConfig);

  return build(config);
}

export function createViteConfig(options: FusionVitePluginUnresolved, tasks?: string | string[], viteConfig: InlineConfig = {}) {
  return defineConfig(mergeConfig<InlineConfig, InlineConfig>(
    {
      configFile: false,
      root: urlToDirname(import.meta.url),
      build: {
        minify: false,
        outDir: './dest',
      },
      plugins: [
        useFusion(options, tasks)
      ],
      logLevel: 'warn'
    },
    viteConfig
  ))
}
