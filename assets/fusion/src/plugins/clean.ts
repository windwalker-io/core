// import chalk from 'chalk';
// import path from "path";
// import { rimraf } from "rimraf";
// import { MaybePromise, OutputOptions, type Plugin } from 'rollup';
//
import { MaybePromise, OutputOptions } from 'rollup';
import { PluginOption } from 'vite';

export default function clean(): PluginOption {
  const cleaned = new Set<string>();

  return {
    name: "fusion:clean",
    // outputOptions(outputOptions) {
    //   if (handler === false) {
    //     return outputOptions;
    //   }
    //
    //   const outDir = outputOptions.dir
    //     ? outputOptions.dir
    //     : outputOptions.file
    //       ? path.dirname(outputOptions.file)
    //       : null;
    //
    //   if (outDir) {
    //     cleaned.add(outDir);
    //   }
    // },
    async generateBundle(rollupOptions) {
      // if (handler === false) {
      //   return;
      // }
      //
      // const promises = cleaned.values().map(async (dir) => {
      //   if (verbose) {
      //     console.log(`Clean: ${chalk.yellow(dir)}`);
      //   }
      //
      //   if (typeof handler === 'function') {
      //     return handler(dir, rollupOptions);
      //   }
      //
      //   if (dir) {
      //     return rimraf(dir);
      //   }
      // });
      //
      // await Promise.all(promises);
    },
  };
}
