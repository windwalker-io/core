import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from '@/processors/ProcessorInterface';
import { CssOptions, TaskInput, TaskOutput } from '@/types';
import { forceArray, handleMaybeArray } from '@/utilities/arr';
import { basename, parse } from 'node:path';
import { MaybePromise } from 'rollup';

export function css(
  input: TaskInput,
  output?: TaskOutput,
  options: CssOptions = {}
): CssProcessor {
  return new CssProcessor(input, output, options);
}

export class CssProcessor implements ProcessorInterface {
  constructor(protected input: TaskInput, protected output?: TaskOutput, protected options: CssOptions = {}) {
  }

  async config(taskName: string, builder: ConfigBuilder) {
    handleMaybeArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);

      builder.assetFileNamesCallbacks.push((assetInfo) => {
        const name = assetInfo.names[0];

        if (!name) {
          return undefined;
        }

        // Rename only if the asset name matches the task id with .css extension
        if (basename(name, '.css') === task.id) {
          if (!this.output) {
            return parse(input).name + '.css';
          }

          return task.normalizeOutput(this.output, '.css');

          // if (!isAbsolute(name)) {
          //   return name;
          // } else {
          //   builder.moveFilesMap[task.id + '.css'] = name;
          // }
        }
      });
    });

    // show(builder)
  }

  preview(): MaybePromise<ProcessorPreview[]> {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || basename(input),
        extra: {}
      };
    });
  }
}
