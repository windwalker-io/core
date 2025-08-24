import { OutputOptions, RollupOptions } from 'rollup';
import { ExtraOptions, TaskOutput } from './types/processors';

export function normalizeOutput(output: TaskOutput, options: Record<string, any> = {}): OutputOptions {
  if (typeof output === 'string') {
    if (output.endsWith('/')) {
      output = {
        dir: output,
        ...options
      };
    } else {
      output = {
        file: output,
        ...options
      };
    }
  }

  return output;
}

export function mergeOptions(base: Partial<RollupOptions>, extra?: ExtraOptions): Partial<RollupOptions> {
  if (!extra) {
    return base;
  }

  if (typeof extra === 'function') {
    base = extra(base) ?? base;
  } else {
    base = { ...base, ...extra };
  }

  return base;
}

