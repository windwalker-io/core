import { stubTrue } from 'lodash-es';
import path from "node:path";
import { defineConfig } from "rollup";
import typescript from "@rollup/plugin-typescript";
import commonjs from "@rollup/plugin-commonjs";
import nodeResolve from "@rollup/plugin-node-resolve";
import dts from "rollup-plugin-dts";

// Mark 3rd party packages as external
const external = (id: string) =>
  // All path that not starts with '.' or '/' are external
  !id.startsWith(".") && !path.isAbsolute(id);

export default defineConfig([
  // --- 1) JS bundles ---
  {
    input: "./src/index.ts",
    output: [
      { file: "dist/index.js", format: "esm", sourcemap: true, inlineDynamicImports: true, },
      { file: "dist/index.cjs", format: "cjs", sourcemap: true, exports: "named", inlineDynamicImports: true, }
    ],
    external,
    plugins: [
      nodeResolve({ extensions: [".mjs", ".js", ".json", ".ts"] }),
      commonjs(),
      typescript({
        tsconfig: "./tsconfig.json"
        // If you are using Node ESM project, consider to add below if necessary:
        // declaration: false, emitDeclarationOnly: false
      })
    ],
    treeshake: { moduleSideEffects: false }
  },

  // --- 2) DTS ---
  {
    input: "./src/index.ts",
    output: { file: "dist/index.d.ts", format: "es" },
    plugins: [dts()],
    external
  },
]);


