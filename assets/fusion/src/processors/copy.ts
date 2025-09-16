import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from '@/processors/ProcessorInterface.ts';
import { TaskInput } from '@/types';
import { forceArray, handleMaybeArray } from '@/utilities/arr.ts';
import { MaybePromise } from 'rollup';

export function copy(input: TaskInput, dest: string) {
  return new CopyProcessor(input, dest);
}

export class CopyProcessor implements ProcessorInterface {
  constructor(public input: TaskInput, public dest: string) {
  }

  config(taskName: string, builder: ConfigBuilder): MaybePromise<void> {
    handleMaybeArray(this.input, (input) => {
      builder.copyTasks.push({ src: input, dest: this.dest, options: {} })
    });
  }

  preview(): MaybePromise<ProcessorPreview[]> {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

