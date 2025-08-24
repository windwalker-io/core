import { RollupOptions } from 'rollup';
import postcss from 'rollup-plugin-postcss';
import { ExtraOptions, TaskInput, TaskOutput } from '../types/processors';
import { mergeOptions, normalizeOutput } from '../utilities';

export async function js(input: TaskInput, output: TaskOutput, options?: ExtraOptions): Promise<RollupOptions> {
  output = normalizeOutput(output);

  let opt: Partial<RollupOptions> = {
    input,
    output,
    plugins: [
      postcss({
        extract: true,
        sourceMap: true,
        use: ['sass']
      }),
    ],
  };

  return mergeOptions(opt, options);
}
