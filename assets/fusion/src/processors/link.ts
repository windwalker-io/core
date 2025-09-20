import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from '@/processors/ProcessorInterface.ts';
import { LinkOptions, TaskInput } from '@/types';
import { forceArray, handleMaybeArray } from '@/utilities/arr.ts';
import { MaybePromise } from '@/types';

export function link(input: TaskInput, dest: string, options: LinkOptions = {}) {
  return new LinkProcessor(input, dest, options);
}

export class LinkProcessor implements ProcessorInterface {
  constructor(public input: TaskInput, public dest: string, public options: LinkOptions = {}) {
  }

  config(taskName: string, builder: ConfigBuilder): MaybePromise<void> {
    handleMaybeArray(this.input, (input) => {
      builder.linkTasks.push({ src: input, dest: this.dest, options: this.options });
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

