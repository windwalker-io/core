import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import { JsOptions, TaskInput, TaskOutput } from '@/types';
import { handleMaybeArray } from '@/utilities/arr';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import { createViteLibOptions, createViteOptions } from '@/utilities/vite';
import { resolve } from 'path';
import { OutputOptions } from 'rollup';
import { ESBuildOptions, mergeConfig, UserConfig } from 'vite';

export async function js(input: TaskInput, output: TaskOutput, options: JsOptions = {}): Promise<UserConfig[]> {
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
          [],
          (config) => {
            return overrideViteJsOptions(config, options);
          }
        );
      }

      return createViteOptions(
        createViteLibOptions(input),
        output,
        [],
        (config) => {
          return overrideViteJsOptions(config, options);
        }
      );
    }
  );
}

export function useJsProcessor(
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

export function overrideViteJsOptions(config: UserConfig, options: JsOptions) {
  const esbuild = mergeOptions<ESBuildOptions>(
    {
      target: options?.target || 'esnext',
    },
    options?.esbuild
  );

  config.build!.minify = options?.minify === MinifyOptions.SAME_FILE ? 'esbuild' : false;
  config.build!.emptyOutDir = options.clean || false;
  config.build!.target = options.target || 'esnext';
  config.esbuild = esbuild;

  config = addExternals(config, options.externals);

  if (options.path) {
    config = mergeConfig(config, { resolve: { alias: {}, } });

    if (typeof options.path === 'string') {
      config.resolve!.alias = {
        '@': resolve(options.path)
      };
    } else {
      const aliases: Record<string, string> = {};

      for (const alias in options.path) {
        aliases[alias] = resolve(options.path[alias]);
      }

      config.resolve!.alias = aliases;
    }
  }

  return config;
}

export function addExternals(config: UserConfig, externals?: Record<string, string>) {
  if (!externals) {
    return config;
  }

  config = mergeConfig(config, { build: { rollupOptions: { external: [] } } });

  if (!Array.isArray(config.build!.rollupOptions!.external)) {
    throw new Error('Only array externals are supported now.');
  }

  for (const ext in externals) {
    if (!config.build!.rollupOptions!.external.includes(ext)) {
      config.build!.rollupOptions!.external.push(ext);
    }
  }

  config.build!.rollupOptions!.output = handleMaybeArray(config.build!.rollupOptions!.output, (output) => {
    output!.globals = {
      ...output!.globals,
        ...externals
    };
    return output;
  });

  return config;

}
