import vuePlugin from '@vitejs/plugin-vue';
import fs from 'fs-extra';
import { resolve } from 'node:path';
import { rimraf } from 'rimraf';
import { defineConfig } from 'vite';
import publicPath from 'vite-plugin-public-path';
import { injectSystemJS, resolveModuleRealpath, systemCSSFix } from './dist/next';

// const dependencies = JSON.parse(readFileSync('./package.json', 'utf8')).dependencies || {};

export default defineConfig({
  resolve: {
    alias: {
      '@': resolve('./src')
    }
  },
  base: '/__vite_base__/',
  build: {
    rollupOptions: {
      input: {
        debugger: './src/debugger/debugger.js',
        console: './scss/debugger-console.scss',
      },
      output: {
        format: 'system',
        inlineDynamicImports: false,
        entryFileNames(chunkInfo) {
          if (chunkInfo.name === 'debugger') {
            return 'debugger.js';
          }

          if (chunkInfo.name === 'console') {
            return 'debugger-console.css';
          }

          return '[name].js';
        },
        chunkFileNames(chunkInfo) {
          return '[name]-[hash].js';
        },
        assetFileNames(assetInfo) {
          if (assetInfo.name === 'console.css') {
            return 'debugger-console.css';
          }

          return '[name][extname]';
        },
      },
      // preserveEntrySignatures: 'strict',
      // external: []
    },
    outDir: 'dist/debugger',
    emptyOutDir: false,
    minify: false,
  },
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ['mixed-decls', 'color-functions', 'global-builtin', 'import']
      },
    }
  },
  plugins: [
    vuePlugin(),
    {
      name: 'custom',
      async buildStart() {
        await rimraf('./dist/debugger');
      },
      writeBundle() {
        fs.moveSync('./dist/debugger/debugger-console.css', './dist/debugger-console.css', { overwrite: true });
      }
    },
    publicPath({
      publicPathExpression: "window.externalPublicPath",
      html: true,
    }),
    systemCSSFix(),
    // injectSystemJS(resolveModuleRealpath(import.meta.url, 'systemjs/dist/system.min.js'))
    // dtsPlugin({
    //   outDir: 'dist',
    //   // tsconfigPath: './tsconfig.json',
    //   insertTypesEntry: true,
    //   // merge to 1 file
    //   rollupTypes: true,
    //   // include: ['./src/next/index.ts'],
    // })
  ]
});
