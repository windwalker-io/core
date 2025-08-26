// import { MinifyOptions } from '@/enum';
// import { isVerbose } from '@/index';
// import clean from '@/plugins/clean';
// import { JsOptions, TaskInput, TaskOutput } from '@/types';
// import { normalizeOutputs } from '@/utilities/output';
// import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
// import vuePlugin, { Options as VueOptions } from '@vitejs/plugin-vue';
// import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';
// import esbuild, { Options as EsbuildOptions } from 'rollup-plugin-esbuild';
//
// export async function vue(input: TaskInput, output: TaskOutput, options: JsOptions = {}): Promise<RollupOptions[]> {
//   function plugins(esbuildOptions: EsbuildOptions, vueOptions: VueOptions = {}) {
//     return [
//       clean(options.clean || false, options.verbose),
//       vuePlugin(
//         mergeOptions(
//           {},
//           vueOptions
//         )
//       ),
//       esbuild(
//         mergeOptions<EsbuildOptions>(
//           {
//             target: options?.target || 'esnext',
//             tsconfig: options?.tsconfig ?? './tsconfig.json',
//           },
//           esbuildOptions
//         )
//       )
//     ];
//   }
//
//   return useJsProcessor({
//     input,
//     output,
//     options,
//     createOptions: (output, isMinify) => {
//       if (isMinify) {
//         return {
//           input,
//           output,
//           plugins: plugins({
//             minify: true,
//             sourceMap: true,
//           })
//         };
//       }
//
//       return {
//         input,
//         output,
//         plugins: plugins({
//           minify: options?.minify === MinifyOptions.SAME_FILE,
//           sourceMap: options?.minify === MinifyOptions.SAME_FILE,
//         })
//       };
//     }
//   });
// }
//
// export function useJsProcessor(
//   params: {
//     input: TaskInput;
//     output: TaskOutput;
//     options: JsOptions;
//     createOptions: (outputs: RollupOptions[], isMinify: boolean) => RollupOptions
//   }
// ) {
//   const { output, options, createOptions } = params;
//
//   options.verbose ??= isVerbose;
//
//   const outputs = normalizeOutputs(output, { format: options?.format || 'es' });
//
//   for (const output of outputs) {
//     if (output.format === 'umd') {
//       output.name = options?.umdName;
//     }
//   }
//
//   const all: RollupOptions[] = [];
//
//   const opt = createOptions(outputs, false);
//   all.push(mergeOptions(opt, options.rollup));
//
//   if (options?.minify === MinifyOptions.SEPARATE_FILE) {
//     const minOutputs = outputs.map((output) => {
//       return appendMinFileName(output);
//     });
//
//     const minOptions = createOptions(minOutputs, true);
//
//     all.push(mergeOptions(minOptions, options?.rollup));
//   }
//
//   return all;
// }
//
// // function createOptions(params: {
// //   input: TaskInput;
// //   output: MaybeArray<OutputOptions>;
// //   plugins?: RollupOptions['plugins'];
// // }): Partial<RollupOptions> {
// //   const { input, output, plugins } = params;
// //
// //   return {
// //     input,
// //     output,
// //     plugins
// //   };
// // }
