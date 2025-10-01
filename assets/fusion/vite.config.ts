import { readFileSync } from 'node:fs';
import { resolve } from 'path';
import { defineConfig } from "vite";
import dts from "unplugin-dts/vite";
const dependencies = JSON.parse(readFileSync('./package.json', 'utf8')).dependencies || {};

export default defineConfig(({ mode }) => {
  return {
    resolve: {
      alias: {
        '@': resolve('./src'),
      },
    },
    build: {
      lib: {
        entry: 'src/index.ts',
        formats: ['es', 'cjs'],
      },
      sourcemap: true,
      rollupOptions: {
        external: [
          /^node:/,
          'fs',
          'path',
          'url',
          'util',
          'crypto',
          'module',
          'os',
          'child_process',
          'worker_threads',
          'tty',
          'vite',
          ...Object.keys(dependencies),
        ],
        output: [
          {
            format: 'es',
            entryFileNames: 'index.js',
            inlineDynamicImports: true,
          },
          {
            format: 'cjs',
            entryFileNames: 'index.cjs',
            inlineDynamicImports: true,
            exports: 'named',
          },
        ],
        treeshake: { moduleSideEffects: false },
      },
      target: 'esnext',
      outDir: 'dist',
      emptyOutDir: true,
      minify: false,
    },
    plugins: [
      dts({
        // entryRoot: 'src',
        // pathsToAliases: true,
        outDirs: 'dist',
        tsconfigPath: './tsconfig.json',
        insertTypesEntry: true,
        // merge to 1 file
        // rollupTypes: true,
        bundleTypes: true,
      }),
    ],
  };
});


