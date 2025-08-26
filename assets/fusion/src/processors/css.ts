import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import { CssOptions, OverrideOptions, TaskInput, TaskOutput } from '@/types';
import { forceArray } from '@/utilities/arr';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions } from '@/utilities/utilities';
import { createViteLibOptions, createViteOptions } from '@/utilities/vite';
import { cloneDeep } from 'lodash-es';
import { MaybeArray, OutputOptions } from 'rollup';
import postcss, { type PostCSSPluginConf } from 'rollup-plugin-postcss';
import { BuildEnvironmentOptions, BuilderOptions, UserConfig } from 'vite';

export async function css(
  input: TaskInput,
  output: TaskOutput,
  options: CssOptions = {}
): Promise<MaybeArray<UserConfig>> {
  options.verbose ??= isVerbose;

  let outputs = normalizeOutputs(output, { format: 'es' });

  const all = [];

  for (const output of outputs) {
    const opt = createOptions(
      input,
      outputs,
      options,
      (config) => {
        config.build!.minify = options.minify === MinifyOptions.SAME_FILE ? 'esbuild' : false;
        config.build!.cssMinify = options.minify === MinifyOptions.SAME_FILE ? 'esbuild' : false;

        return config;
      },
    );

    all.push(mergeOptions(opt, options?.vite));

    if (options?.minify === MinifyOptions.SEPARATE_FILE) {
      const minOutput = appendMinFileName(output);

      const opt = createOptions(
        input,
        minOutput,
        options,
        (config) => {
          config.build!.minify = 'esbuild';
          config.build!.cssMinify = 'esbuild';

          return config;
        },
      );

      all.push(mergeOptions(opt, options?.vite));
    }
  }

  return all;
}

function createOptions(
  input: TaskInput,
  output: MaybeArray<OutputOptions>,
  options: CssOptions,
  override?: OverrideOptions<UserConfig>
): Partial<UserConfig> {
  output = cloneDeep(output);

  const config = createViteOptions(
    undefined,
    output,
    (config) => {
      config.build!.rollupOptions!.input = input;

      for (const o of forceArray(config.build!.rollupOptions!.output) as OutputOptions[]) {
        o.assetFileNames = String(o.entryFileNames);

        delete o.entryFileNames;
      }

      config.build!.cssCodeSplit = true;
      config.css = {
        modules: {
          scopeBehaviour: 'global', // 或是 'global'
        },
        transformer: 'postcss',
      };

      // Remove __placeholder__ file since Vite must use it to extract CSS.
      config.plugins = [
        {
          name: 'drop-vite-facade-css',
          generateBundle(_, bundle) {
            for (const [fileName, asset] of Object.entries(bundle)) {
              if (
                asset.type === 'asset'
                && fileName === '__plaecholder__.min.css'
              ) {
                delete bundle[fileName];
              }
            }
          },
        }
      ]

      return config;
    }
  );

  return mergeOptions(
    config,
    override,
    options.vite
  )
}
