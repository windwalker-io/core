import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import dtsPlugin from 'unplugin-dts/vite';

const dependencies = JSON.parse(readFileSync('./package.json', 'utf8')).dependencies || {};

export default defineConfig(({ mode }) => {
  return {
    resolve: {
      alias: {
        '@': resolve('./src')
      }
    },
    build: {
      lib: {
        entry: './src/next/index.ts',
        formats: ['es'],
        name: 'next',
        fileName: (format) => `next.js`
      },
      rollupOptions: {
        // input: {
        //   next: './src/next/index.ts',
        // },
        output: {
          // format: 'esm',
          // preserveModules: false,
          entryFileNames(chunkInfo) {
            if (chunkInfo.name === 'index') {
              return 'next.js';
            }

            return '[name].js';
          },
          assetFileNames(assetInfo) {
            return 'chunks/[name]-[hash].[extname]';
          },
        },
        preserveEntrySignatures: 'strict',
        external: [
          /^node:/,
          /fusion-next/,
          'rollup',
          'vite',
          'fast-glob',
          'fs-extra',
          'micromatch',
          'is-glob',
          ...Object.keys(dependencies),
          // 'util',
          // 'stream',
          // 'fs',
          // 'os',
          // 'path',
          // 'path',
        ]
      },
      outDir: 'dist',
      emptyOutDir: false,
      minify: false,
    },
    plugins: [
      dtsPlugin({
        outDirs: 'dist',
        tsconfigPath: './tsconfig.json',
        insertTypesEntry: true,
        // merge to 1 file
        bundleTypes: true,
        exclude: ['./src/*.mjs']
        // include: ['./src/next/index.ts'],
      }),
    ]
  };
});
