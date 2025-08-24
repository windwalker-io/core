import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import clean from '@/plugins/clean';
import { JsOptions, TaskInput, TaskOutput } from '@/types';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';
import esbuild, { Options as EsbuildOptions } from 'rollup-plugin-esbuild';

export async function js(input: TaskInput, output: TaskOutput, options: JsOptions = {}): Promise<RollupOptions[]> {
  options.verbose ??= isVerbose;

  const outputs = normalizeOutputs(output, { format: options?.format || 'es' });

  for (const output of outputs) {
    if (output.format === 'umd') {
      output.name = options?.umdName;
    }
  }

  const all: RollupOptions[] = [];

  const opt = createOptions(input, outputs, options, {
    minify: options?.minify === MinifyOptions.SAME_FILE,
    sourceMap: options?.minify === MinifyOptions.SAME_FILE,
  });
  all.push(mergeOptions(opt, options.rollup));

  if (options?.minify === MinifyOptions.SEPARATE_FILE) {
    const minOutputs = outputs.map((output) => {
      return appendMinFileName(output);
    });

    const minOptions = createOptions(input, minOutputs, options,{
      minify: true,
      sourceMap: true,
    });

    all.push(mergeOptions(minOptions, options?.rollup));
  }

  return all;
}

function createOptions(
  input: TaskInput,
  output: MaybeArray<OutputOptions>,
  options: JsOptions,
  esbuildOptions?: EsbuildOptions
): Partial<RollupOptions> {
  return {
    input,
    output,
    plugins: [
      clean(options.clean || false, options.verbose),
      esbuild(
        mergeOptions<EsbuildOptions>(
          {
            target: options?.target || 'esnext',
            tsconfig: options?.tsconfig ?? './tsconfig.json',
          },
          esbuildOptions
        )
      )
    ],
  };
}
