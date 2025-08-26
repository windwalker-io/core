import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import clean from '@/plugins/clean';
import { JsOptions, TaskInput, TaskOutput } from '@/types';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import esbuild, { Options as EsbuildOptions } from 'rollup-plugin-esbuild';
import { UserConfig } from 'vite';

export async function js(input: TaskInput, output: TaskOutput, options: JsOptions = {}): Promise<UserConfig[]> {
  function plugins(esbuildOptions: EsbuildOptions) {
    return [
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
    ];
  }

  return useJsProcessor(
    output,
    options,
    (output, isMinify) => {
      if (isMinify) {
        return {
          input,
          output,
          plugins: plugins({
            minify: true,
            sourceMap: true,
          })
        };
      }

      return {
        input,
        output,
        plugins: plugins({
          minify: options?.minify === MinifyOptions.SAME_FILE,
          sourceMap: options?.minify === MinifyOptions.SAME_FILE,
        })
      };
    }
  );
}

function useJsProcessor(
  output: TaskOutput,
  options: JsOptions,
  createOptions: (outputs: UserConfig[], isMinify: boolean) => UserConfig
) {
  options.verbose ??= isVerbose;

  const outputs = normalizeOutputs(output, { format: options?.format || 'es' });

  for (const output of outputs) {
    if (output.format === 'umd') {
      output.name = options?.umdName;
    }
  }

  const all: UserConfig[] = [];

  const opt = createOptions(outputs, false);
  all.push(mergeOptions(opt, options.vite));

  if (options?.minify === MinifyOptions.SEPARATE_FILE) {
    const minOutputs = outputs.map((output) => {
      return appendMinFileName(output);
    });

    const minOptions = createOptions(minOutputs, true);

    all.push(mergeOptions(minOptions, options?.vite));
  }

  return all;
}
