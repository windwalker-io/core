import { isDev, isProd } from '@/index';
import { addExternals, overrideViteJsOptions, useJsProcessor } from '@/processors/js';
import { TaskInput, TaskOutput } from '@/types';
import { VueOptions } from '@/types/vue';
import { handleMaybeArray } from '@/utilities/arr';
import { createViteLibOptions, createViteOptions } from '@/utilities/vite';
import vuePlugin from '@vitejs/plugin-vue';
import { inspect } from 'node:util';
import { mergeConfig, UserConfig } from 'vite';

export async function vue(input: TaskInput, output: TaskOutput, options: VueOptions = {}): Promise<UserConfig[]> {
  return useJsProcessor(
    output,
    options,
    (output, isMinify) => {
      return createViteOptions(
        createViteLibOptions(input),
        output,
        [
          vuePlugin()
        ],
        (config) => {
          config = overrideViteJsOptions(config, options);
          config.build!.sourcemap = isDev ? 'inline' : false;
          return config;
        }
      );
    }
  );
}
