import { MinifyOptions } from '@/enum';
import { JsOptions, TaskInput, TaskOutput } from '@/types';
import { handleMaybeArray } from '@/utilities/arr';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';

export async function js(input: TaskInput, output: TaskOutput, options?: JsOptions): Promise<RollupOptions[]> {
  output = handleMaybeArray(
    normalizeOutputs(output, { format: options?.format || 'es' }),
    (output) => {
      if (output.format === 'umd') {
        output.name = options?.umdName;
      }

      return output;
    }
  );

  const opts = [];

  let opt: Partial<RollupOptions> = createOptions(input, output);
  opts.push(mergeOptions(opt, options?.rollup));

  if (options?.minify === MinifyOptions.SEPARATE_FILE) {
    let opt = createOptions(
      input,
      appendMinFileName(output),
      options,
    );

    opts.push(mergeOptions(opt, options?.rollup));
  }

  return opts
}

function createOptions(input: TaskInput, output: MaybeArray<OutputOptions>, options?: JsOptions): Partial<RollupOptions> {
  return {
    input,
    output,
    plugins: [
      //
    ],
  };
}
