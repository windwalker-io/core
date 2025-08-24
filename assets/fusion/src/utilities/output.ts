import { TaskOutput } from '@/types';
import { forceArray, handleMaybeArray } from '@/utilities/arr';
import { MaybeArray, OutputOptions } from 'rollup';

export function normalizeOutputs(
  output: TaskOutput,
  defaultOptions: Record<string, any> = {}
): OutputOptions[] {
  output = handleMaybeArray(output, (output) => {
    if (typeof output === 'string') {
      if (output.endsWith('/')) {
        output = {
          dir: output,
          ...defaultOptions
        };
      } else {
        output = {
          file: output,
          ...defaultOptions
        };
      }
    }

    return output;
  });

  return forceArray(output);
}

function normalizeOutputObject(output: OutputOptions | string, defaultOptions: Record<string, any> = {}) {
  if (typeof output === 'string') {
    if (output.endsWith('/')) {
      output = {
        dir: output,
        ...defaultOptions
      };
    } else {
      output = {
        file: output,
        ...defaultOptions
      };
    }
  }

  return output;
}
