import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import { JsOptions, TaskInput, TaskOutput } from '@/types';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import { createViteLibOptions, createViteOptions } from '@/utilities/vite';
import { OutputOptions } from 'rollup';
import { ESBuildOptions, UserConfig } from 'vite';

export async function js(input: TaskInput, output: TaskOutput, options: JsOptions = {}): Promise<UserConfig[]> {
  const esbuild = mergeOptions<ESBuildOptions>(
    {
      target: options?.target || 'esnext',
    },
    options?.esbuild
  );

  // if (typeof options.tsconfig === 'string') {
  //   esbuild.tsconfig = options.tsconfig;
  // } else if (typeof options.tsconfig === 'object') {
  //   esbuild.tsconfigRaw = options.tsconfig;
  // }

  return useJsProcessor(
    output,
    options,
    (output, isMinify) => {
      if (isMinify) {
        return createViteOptions(
          createViteLibOptions(input),
          output,
          (config) => {
            config.build!.minify = 'esbuild';
            config.build!.emptyOutDir = options.clean || false;
            config.build!.target = options.target || 'esnext';
            config.esbuild = esbuild;

            return config;
          }
        );
      }

      return createViteOptions(
        createViteLibOptions(input),
        output,
        (config) => {
          config.build!.minify = options?.minify === MinifyOptions.SAME_FILE ? 'esbuild' : false;
          config.build!.emptyOutDir = options.clean || false;
          config.build!.target = options.target || 'esnext';
          config.esbuild = esbuild;

          return config;
        }
      );
    }
  );
}

function useJsProcessor(
  output: TaskOutput,
  options: JsOptions,
  createOptions: (outputs: OutputOptions[], isMinify: boolean) => UserConfig
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
