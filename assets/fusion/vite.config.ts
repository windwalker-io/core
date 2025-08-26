import path from "node:path";
import { resolve } from 'path';
import { defineConfig } from "vite";
import dts from "vite-plugin-dts";

// Mark 3rd party packages as external
const external = (id: string) =>
  // All path that not starts with '.' or '/' are external
  !id.startsWith(".") && !path.isAbsolute(id) && !id.startsWith("@/");

export default defineConfig({
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
      external,
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
  },
  plugins: [
    dts({
      // entryRoot: 'src',
      // outDir: 'dist',
      tsconfigPath: './tsconfig.json',
      insertTypesEntry: true,
      // merge to 1 file
      // rollupTypes: true,
    }),
  ],
});


