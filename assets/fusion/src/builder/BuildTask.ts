import { shortHash } from '@/utilities/crypto.ts';
import { uniqueId } from 'lodash-es';
import { normalize, basename, resolve, isAbsolute, parse } from 'node:path';
import { PreRenderedChunk } from 'rollup';

export default class BuildTask {
  id: string;
  output?: string | ((chunkInfo: PreRenderedChunk) => any);
  postCallbacks: (() => void)[] = [];

  constructor(public input: string, public group?: string) {
    this.id = BuildTask.toFileId(input, group);

    this.input = normalize(input);
  }

  dest(output?: string | ((chunkInfo: PreRenderedChunk) => any)) {
    if (typeof output === 'string') {
      output = this.normalizeOutput(output);
    }

    this.output = output;

    return this;
  }

  addPostCallback(callback: () => void) {
    this.postCallbacks.push(callback);
    return this;
  }

  normalizeOutput(output: string, ext = '.js') {
    if (output.endsWith('/') || output.endsWith('\\')) {
      output += parse(this.input).name + ext;
    }

    // if (output.startsWith('.')) {
    //   output = resolve(output);
    // }

    return output;
  }

  static toFileId(input: string, group?: string) {
    input = normalize(input);

    group ||= uniqueId();

    return group + '-' + shortHash(input);
  }
}

