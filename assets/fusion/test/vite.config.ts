import { defineConfig } from 'vite';
import { useFusion } from '../dist';

export default defineConfig({
  // resolve: {
  //   alias: {
  //     '@': new URL('./src', import.meta.url).pathname
  //   }
  // },
  build: {
    // outDir:
  },
  plugins: [
    useFusion({
      fusionfile: await import('./fusionfile')
    })
  ]
});
