import BuildTask from '../builder/BuildTask.ts';
import ConfigBuilder from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from '../processors/ProcessorInterface.ts';
import { TaskInput, TaskOutput } from '../types';
import { forceArray, handleForceArray, handleMaybeArray } from '../utilities/arr';
import { basename, parse } from 'node:path';
import { MaybePromise } from '../types';

export function js(input: TaskInput, output?: TaskOutput): ProcessorInterface {
  return new JsProcessor(input, output);
}

export class JsProcessor implements ProcessorInterface {

  constructor(public input: TaskInput, public output?: TaskOutput) {
  }

  config(taskName: string, builder: ConfigBuilder): BuildTask[] {
    return handleForceArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);

      builder.entryFileNamesCallbacks.push((chunkInfo) => {
        const name = chunkInfo.name;

        if (!name) {
          return;
        }

        // Rename only if the asset name matches the task id with .css extension
        if (name === task.id) {
          if (!this.output) {
            return parse(input).name + '.js';
          }

          return task.normalizeOutput(this.output);

          // if (!isAbsolute(name)) {
          //   return name;
          // } else {
          //   builder.moveFilesMap[task.id + '.css'] = name;
          // }
        }
      });

      return task;
    });
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
