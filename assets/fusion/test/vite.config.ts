import { defineConfig } from 'vite';
import { useFusion } from '../dist';

export default defineConfig({
  // resolve: {
  //   alias: {
  //     '@': new URL('./src', import.meta.url).pathname
  //   }
  // },
  build: {
    outDir: './dest'
  },
  plugins: [
    useFusion({
      // configFile: './fusionfile.ts'
    })
  ]
});
