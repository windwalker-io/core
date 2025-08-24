import { handleMaybeArray } from '@/utilities/arr';
import { cloneDeep } from 'lodash-es';
import { MaybeArray, OutputOptions, RollupOptions } from 'rollup';
import { OverrideOptions } from '@/types';

export function mergeOptions<T = RollupOptions>(
  base: Partial<T>,
  ...overrides: (OverrideOptions<T> | undefined)[]
): Partial<T> {
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
      base = { ...base, ...override };
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
