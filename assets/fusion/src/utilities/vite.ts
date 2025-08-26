import { OverrideOptions, TaskInput } from '@/types';
import { mergeOptions } from '@/utilities/utilities';
import { MaybeArray, OutputOptions } from 'rollup';
import { defineConfig, LibraryOptions, UserConfig } from 'vite';

export function defineAllConfigs(configs: UserConfig[]) {
  return configs.map(defineConfig);
}

export function createViteLibOptions(input: TaskInput, extraOptions?: OverrideOptions<LibraryOptions>): LibraryOptions {
  return mergeOptions<LibraryOptions>(
    {
      entry: input,
    },
    extraOptions
  ) as LibraryOptions;
}

export function createViteOptions(
  lib?: LibraryOptions,
  output?: MaybeArray<OutputOptions>,
  override?: OverrideOptions<UserConfig>
): Partial<UserConfig> {
  return mergeOptions(
    {
      build: {
        lib,
        rollupOptions: {
          output,
        },
        emptyOutDir: false,
        target: 'esnext',
      },
    },
    override
  );
}

