import BuildTask from '@/builder/BuildTask.ts';
import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { MinifyOptions } from '@/enum';
import { isVerbose } from '@/index';
import { ProcessorInterface, ProcessorPreview } from '@/processors/ProcessorInterface';
import { CssOptions, OverrideOptions, TaskInput, TaskOutput } from '@/types';
import { forceArray, handleMaybeArray } from '@/utilities/arr';
import { normalizeOutputs } from '@/utilities/output';
import { appendMinFileName, mergeOptions, show } from '@/utilities/utilities';
import { createViteOptions } from '@/utilities/vite';
import autoprefixer from 'autoprefixer';
import { cloneDeep } from 'lodash-es';
import type { AcceptedPlugin, ProcessOptions } from 'postcss';
import { MaybeArray, MaybePromise, OutputOptions } from 'rollup';
import { UserConfig } from 'vite';
import { basename, isAbsolute, resolve } from 'node:path';

export function css(
  input: TaskInput,
  output: TaskOutput,
  options: CssOptions = {}
): CssProcessor {
  return new CssProcessor(input, output, options);
}

export class CssProcessor implements ProcessorInterface {
  constructor(protected input: TaskInput, protected output: TaskOutput, protected options: CssOptions = {}) {
  }

  async config(taskName: string, builder: ConfigBuilder) {
    handleMaybeArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);

      builder.assetFileNamesCallbacks.push((assetInfo) => {
        const name = assetInfo.names[0];
        if (!name) {
          return undefined;
        }

        if (basename(name, '.css') === task.id) {
          const name = task.normalizeOutput(this.output);

          if (!isAbsolute(name)) {
            return name;
          } else {
            builder.moveFilesMap[task.id + '.css'] = name;
          }
        }
      });
    });

    // show(builder)
  }

  preview(): MaybePromise<ProcessorPreview[]> {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output,
        extra: {}
      };
    });
  }
}

export async function cssBak(
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
    [],
    (config) => {
      config.build!.rollupOptions!.input = input;
      config.build!.emptyOutDir = options.clean ?? false;

      for (const o of forceArray(config.build!.rollupOptions!.output) as OutputOptions[]) {
        o.assetFileNames = String(o.entryFileNames);

        delete o.entryFileNames;
      }

      config.build!.cssCodeSplit = true;
      config.css = {
        // modules: {
        //   scopeBehaviour: 'global', // 或是 'global'
        // },
        transformer: 'postcss',
        postcss: mergeOptions<ProcessOptions & AcceptedPlugin>(
          {
            plugins: [
              autoprefixer({ overrideBrowserslist: options.browserslist })
            ]
          },
          options.postcss
        ),
      };

      return config;
    }
  );

  return mergeOptions(
    config,
    override,
    options.vite
  );
}
