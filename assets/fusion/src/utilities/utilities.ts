import { OverrideOptions } from '@/types';
import { cloneDeep, merge } from 'lodash-es';
import { inspect } from 'node:util';
import { OutputOptions } from 'rollup';
import { UserConfig } from 'vite';

export function mergeOptions<T = UserConfig>(
  base: Partial<T> | undefined,
  ...overrides: (OverrideOptions<T> | undefined)[]
): Partial<T> {
  base ??= {};

  if (!overrides.length) {
    return base;
  }

  for (const override of overrides) {
    if (!override) {
      continue;
    }

    if (typeof override === 'function') {
      base = override(base) ?? base;
    } else {
      base = merge(base, override);
    }
  }

  return base;
}

export function appendMinFileName(output: OutputOptions): OutputOptions {
  output = cloneDeep(output);

  if (output.file) {
    const parts = output.file.split('.');
    const ext = parts.pop();
    output.file = `${parts.join('.')}.min.${ext}`;
  } else if (output.dir && typeof output.entryFileNames === 'string') {
    const parts = output.entryFileNames.split('.');
    const ext = parts.pop();
    output.entryFileNames = `${parts.join('.')}.min.${ext}`;
  }

  return output;
}

export function show(data: any, depth = 10) {
  console.log(inspect(data, { depth: null, colors: true }));
}
