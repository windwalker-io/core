import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import clean from '@/plugins/clean';
import { CssOptions, TaskInput, TaskOutput } from '@/types';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import autoprefixer from 'autoprefixer';
import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';
import postcss, { type PostCSSPluginConf } from 'rollup-plugin-postcss';

export async function css(
  input: TaskInput,
  output: TaskOutput,
  options: CssOptions = {}
): Promise<MaybeArray<RollupOptions>> {
  options.verbose ??= isVerbose;

  let outputs = normalizeOutputs(output, { format: 'es' });

  const allOutputs = [];

  for (const output of outputs) {
    const opt = createOptions(
      input,
      outputs,
      options,
      {
        sourceMap: options?.minify === MinifyOptions.SAME_FILE,
        minimize: options?.minify === MinifyOptions.SAME_FILE,
      }
    );

    allOutputs.push(mergeOptions(opt, options?.rollup));

    if (options?.minify === MinifyOptions.SEPARATE_FILE) {
      const minOutput = appendMinFileName(output);

      const opt = createOptions(
        input,
        minOutput,
        options,
        {
          sourceMap: true,
          minimize: true,
        }
      );

      allOutputs.push(mergeOptions(opt, options?.rollup));
    }
  }

  return allOutputs;
}

function createOptions(
  input: TaskInput,
  output: MaybeArray<OutputOptions>,
  options: CssOptions,
  postcssOptions?: Partial<PostCSSPluginConf>
): Partial<RollupOptions> {
  return {
    input,
    output,
    plugins: [
      clean(options.clean || false, options.verbose),
      postcss(
        mergeOptions(
          {
            extract: true,
            use: ['sass'],
            plugins: [
              autoprefixer({
                overrideBrowserslist: options.browserslist
              })
            ],
          },
          postcssOptions,
          options.postcss,
        )
      ),
    ],
  };
}
