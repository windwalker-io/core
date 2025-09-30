import ConfigBuilder from '../builder/ConfigBuilder.ts';
import { ProcessorInterface, ProcessorPreview } from '../processors/ProcessorInterface.ts';
import { MaybePromise } from '../types';

type CallbackHandler = (taskName: string, builder: ConfigBuilder) => MaybePromise<any>;

export function callback(handler: CallbackHandler) {
  return new CallbackProcessor(handler);
}

export function callbackAfterBuild(handler: CallbackHandler) {
  return new CallbackProcessor(handler, true);
}

class CallbackProcessor implements ProcessorInterface {
  constructor(
    /** @internal */
    private handler: CallbackHandler,
    /** @internal */
    private afterBuild = false
  ) {
  }

  config(taskName: string, builder: ConfigBuilder): MaybePromise<any> {
    if (this.afterBuild) {
      builder.postBuildCallbacks.push(() => this.handler(taskName, builder));
    } else {
      this.handler(taskName, builder);
    }

    return undefined;
  }

  preview(): MaybePromise<ProcessorPreview[]> {
    return [];
  }
}

