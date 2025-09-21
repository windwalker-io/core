import { findFilesFromGlobArray, stripUrlQuery } from '@/next';
import {
  type BuildTask,
  type ConfigBuilder,
  js,
  type MaybePromise,
  type ProcessorInterface,
  type ProcessorPreview, shortHash
} from '@windwalker-io/fusion-next';
import fs from 'fs-extra';
import { parse } from 'node-html-parser';
import { normalize, resolve } from 'node:path';

export interface JsModulizeOptions {
  tmpPath?: string;
  cleanTmp?: boolean;
}

export function jsModulize(entry: string, dest: string, options: JsModulizeOptions = {}) {
  return new JsModulizeProcessor(js(entry, dest), options);
}

type ParsedModule = { main: string; scripts: string[]; };

export class JsModulizeProcessor implements ProcessorInterface {
  protected scriptPatterns: string[] = [];
  protected bladePatterns: string[] = [];
  protected stagePrefix: string = '';

  constructor(protected processor: ReturnType<typeof js>, protected options: JsModulizeOptions = {}) {
  }

  config(taskName: string, builder: ConfigBuilder) {
    const tasks = this.processor.config(taskName, builder) as BuildTask[];
    const task = tasks[0];
    const tmpPath = this.options.tmpPath ?? resolve('./tmp/fusion/jsmodules/').replace(/\\/g, '/');
    const clean = this.options.cleanTmp ?? true;

    if (clean) {
      builder.postBuildCallbacks.push(() => {
        fs.removeSync(tmpPath);
      });
    }

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
        const files = findFilesFromGlobArray(this.scriptPatterns);
        let listJS = "{\n";

        // Merge standalone ts files
        for (const file of files) {
          let fullpath = file.fullpath;

          if (fullpath.endsWith('.d.ts')) {
            continue;
          }

          let key = file.relativePath.replace(/assets\//, '').toLowerCase();
          fullpath = resolve(fullpath).replace(/\\/g, '/');

          key = key.substring(0, key.lastIndexOf('.')) + '.js';

          if (this.stagePrefix) {
            key = this.stagePrefix + '/' + key;
          }

          listJS += `'${key}': () => import('${fullpath}'),\n`;
        }

        // Parse from blades
        const results = parseScriptsFromBlades(this.bladePatterns);

        fs.ensureDirSync(tmpPath);

        for (const result of results) {
          let key = result.as;
          const tmpFile = tmpPath + '/' + result.path.replace(/\\|\//g, '_') + '-' + shortHash(result.code) + '.ts';
          fs.writeFileSync(tmpFile, result.code);

          // if (this.stagePrefix) {
          //   key = this.stagePrefix + '/' + key;
          // }

          listJS += `'inline:${key}': () => import('${tmpFile}'),\n`;
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

  mergeScripts(...patterns: (string | string[])[]) {
    this.scriptPatterns = this.scriptPatterns.concat(patterns.flat());

    return this;
  }

  parseBlades(...bladePatterns: (string[] | string)[]) {
    this.bladePatterns = this.bladePatterns.concat(bladePatterns.flat());

    return this;
  }

  stage(stage: string) {
    this.stagePrefix = stage;

    return this;
  }
}

interface ScriptResult {
  as: string;
  file: string;
  path: string;
  code: string;
}

// function parseScriptsFromBlade(file: string, modules: Record<string, ParsedModule>): string[] {
//   const bladeText = fs.readFileSync(file, 'utf8');
//
//   const html = parse(bladeText);
//
//   return html.querySelectorAll('script[lang]')
//     .filter(
//       (el) => ['ts', 'typescript'].includes(el.getAttribute('lang') || '')
//     )
//     .map((el) => el.innerHTML)
//     .filter((c) => c.trim() !== '');
// }

function parseScriptsFromBlades(patterns: string | string[]): ScriptResult[] {
  let files = findFilesFromGlobArray(Array.isArray(patterns) ? patterns : [patterns]);

  return files.map((file) => {
    const bladeText = fs.readFileSync(file.fullpath, 'utf8');

    const html = parse(bladeText);
    // const key = file.relativePath.replace(/.blade.php$/, '').toLowerCase();

    return html.querySelectorAll('script[lang][data-as]')
      .filter(
        (el) => ['ts', 'typescript'].includes(el.getAttribute('lang') || '')
      )
      .map((el) => ({
        as: el.getAttribute('data-as') || '',
        file: file.relativePath,
        path: file.relativePath.replace(/.blade.php$/, ''),
        code: el.innerHTML
      }))
      .filter((c) => c.code.trim() !== '');
  })
    .flat();
}
