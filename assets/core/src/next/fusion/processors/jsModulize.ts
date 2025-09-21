import { findFilesFromGlobArray, stripUrlQuery } from '@/next';
import {
  BuildTask,
  type ConfigBuilder,
  js,
  type MaybePromise,
  type ProcessorInterface,
  type ProcessorPreview
} from '@windwalker-io/fusion-next';
import fg from 'fast-glob';
import fs from 'fs-extra';
import { normalize, resolve } from 'node:path';

export function jsModulize(entry: string, dest: string) {
  return new JsModulizeProcessor(js(entry, dest));
}

export class JsModulizeProcessor implements ProcessorInterface {
  jsPatterns: string[] = [];

  constructor(protected processor: ReturnType<typeof js>) {
  }

  config(taskName: string, builder: ConfigBuilder) {
    const tasks = this.processor.config(taskName, builder) as BuildTask[];
    const task = tasks[0];

    builder.merge({
      resolve: {
        alias: {
          '@main': task.input
        }
      }
    });

    builder.loadCallbacks.push((src, options) => {
      const file = stripUrlQuery(src);

      if (normalize(file) === resolve(task.input)) {
        const files = findFilesFromGlobArray(this.jsPatterns);

        let listJS = "{\n";

        for (const file of files) {
          let fullpath = file.fullpath;

          if (fullpath.endsWith('.d.ts')) {
            continue;
          }

          let key = file.relativePath.replace(/assets$/, '').toLowerCase();
          fullpath = resolve(fullpath).replace(/\\/g, '/');

          key = key.substring(0, key.lastIndexOf('.'));
          listJS += `'${key}': () => import('${fullpath}'),\n`;
        }

        listJS += "}";

        const loaderPath = resolve('./vendor/windwalker/core/assets/core/src/loader/core-loader.ts')
          .replace(/\\/g, '/');

        const ts = `
import { CoreLoader } from '${loaderPath}';

const loader = new CoreLoader();
loader.register(${listJS});

export { loader };
  `;

        return fs.readFileSync(file, 'utf-8') + `\n\n` + ts;
      }
    });

    return undefined;
  }

  preview(): MaybePromise<ProcessorPreview[]> {
    return [];
  }

  modules(...patterns: (string | string[])[]) {
    this.jsPatterns = this.jsPatterns.concat(patterns.flat());

    return this;
  }
}
