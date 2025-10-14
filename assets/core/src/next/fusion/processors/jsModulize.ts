import {
  type BuildTask,
  type ConfigBuilder,
  js,
  type MaybePromise,
  plugin as addPlugin,
  type ProcessorInterface,
  type ProcessorPreview,
  shortHash,
} from '@windwalker-io/fusion-next';
import fs from 'fs-extra';
import { parse } from 'node-html-parser';
import crypto from 'node:crypto';
import { normalize, resolve } from 'node:path';
import { type FindFileResult, findFilesFromGlobArray, findModules, findPackages, stripUrlQuery } from '../../utilities';

export interface JsModulizeOptions {
  tmpPath?: string;
  cleanTmp?: boolean;
}

export function jsModulize(entry: string, dest: string, options: JsModulizeOptions = {}) {
  return new JsModulizeProcessor(js(entry, dest), options);
}

export interface JsModulizeDeepOptions extends JsModulizeOptions {
  mergeScripts?: boolean;
  parseBlades?: boolean;
}

export function jsModulizeDeep(stage: string, entry: string, dest: string, options: JsModulizeDeepOptions = {}) {
  const processor = jsModulize(entry, dest, options)
    .stage(stage.toLowerCase());

  if (options.mergeScripts ?? true) {
    processor.mergeScripts(
      findModules(`${stage}/**/assets/*.ts`),
    );
  }

  if (options.parseBlades ?? true) {
    processor.parseBlades(
      findModules(`${stage}/**/*.blade.php`),
      findPackages('views/**/*.blade.php'),
    );
  }

  return processor;
}

export class JsModulizeProcessor implements ProcessorInterface {
  protected scriptPatterns: string[] = [];
  protected bladePatterns: string[] = [];
  protected stagePrefix: string = '';

  constructor(protected processor: ReturnType<typeof js>, protected options: JsModulizeOptions = {}) {
  }

  config(taskName: string, builder: ConfigBuilder) {
    const tasks = this.processor.config(taskName, builder) as BuildTask[];
    const task = tasks[0];
    const inputFile = resolve(task.input);
    const tmpPath = this.options.tmpPath ?? resolve('./tmp/fusion/jsmodules/').replace(/\\/g, '/');
    const clean = this.options.cleanTmp ?? true;

    if (clean) {
      builder.postBuildCallbacks.push((options, bundle) => {
        fs.removeSync(tmpPath);
      });
      builder.serverStopCallbacks.push((options, bundle) => {
        fs.removeSync(tmpPath);
      });
    }

    this.ignoreMainImport(task);

    builder.resolveIdCallbacks.push((id) => {
      if (id === '@main') {
        return { id, external: true };
      }
    });

    // Todo: Must dynamic changes in load() hook
    const scriptFiles = findFilesFromGlobArray(this.scriptPatterns);
    const bladeFiles = findBladeFiles(this.bladePatterns);

    // Watches
    // Currently we don't watch blade files because not necessary to reload full-pages.
    // for (const bladeFile of bladeScripts) {
    //   builder.watches.push({
    //     file: resolve(bladeFile.file.fullpath),
    //     moduleFile: inputFile,
    //     updateType: 'js-update',
    //   } satisfies WatchTask);
    // }

    builder.loadCallbacks.push((src, options) => {
      const srcFile = stripUrlQuery(src);
      const scripts: Record<string, string> = {};
      
      // if (src === appSrcFileName) {
      if (normalize(srcFile) === inputFile) {
        const bladeScripts = parseScriptsFromBlades(bladeFiles);

        // Merge standalone ts files
        for (const scriptFile of scriptFiles) {
          let fullpath = scriptFile.fullpath;

          if (fullpath.endsWith('.d.ts')) {
            continue;
          }

          let key = scriptFile.relativePath.replace(/assets\//, '').toLowerCase();
          fullpath = resolve(fullpath).replace(/\\/g, '/');

          key = key.substring(0, key.lastIndexOf('.'));

          if (this.stagePrefix) {
            key = this.stagePrefix + '/' + key;
          }

          // md5
          key = 'view:' + crypto.createHash('md5').update(key).digest('hex');

          scripts[key] = fullpath;
        }

        // Parse from blades
        const listens: string[] = [];

        fs.ensureDirSync(tmpPath);

        for (const result of bladeScripts) {
          let key = result.as;
          const filename = result.path.replace(/\\|\//g, '_');
          const tmpFile = tmpPath + '/' + filename + '.ts';

          if (!fs.existsSync(tmpFile) || fs.readFileSync(tmpFile, 'utf8') !== result.code) {
            fs.writeFileSync(tmpFile, result.code);
          }

          scripts[`inline:${key}`] = tmpFile;

          const fullpath = resolve(result.file.fullpath).replace(/\\/g, '/');

          if (!listens.includes(fullpath)) {
            listens.push(fullpath);
          }
        }

        let listJS = `{\n`;

        for (const key in scripts) {
          const fullpath = scripts[key];
          listJS += `'${key}': () => import('${fullpath}'),\n`;
        }

        listJS += `}`;

        // Listen extra files
        builder.watches.push(...listens);

        let { code, comments } = stripComments(fs.readFileSync(srcFile, 'utf-8'));

        // Replace `defineJsModules(...)`
        code = code.replace(/defineJsModules\((.*?)\)/g, listJS);

        return restoreComments(code, comments);
      }
    });

    return undefined;
  }

  /**
   * @see https://github.com/vitejs/vite/issues/6393#issuecomment-1006819717
   * @see https://stackoverflow.com/questions/76259677/vite-dev-server-throws-error-when-resolving-external-path-from-importmap
   */
  private ignoreMainImport(task: BuildTask) {
    const VALID_ID_PREFIX = `/@id/`;
    const importKeys = ['@main'];
    const reg = new RegExp(
      `${VALID_ID_PREFIX}(${importKeys.join("|")})`,
      "g"
    );

    addPlugin({
      name: 'keep-main-external-' + task.id,
      transform(code) {
        return reg.test(code) ? code.replace(reg, (m, s1) => s1) : code;
      }
    });
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
  file: FindFileResult;
  path: string;
  code: string;
}

// function parseScriptsFromBlade(file: string, service: Record<string, ParsedModule>): string[] {
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

function findBladeFiles(patterns: string | string[]) {
  return findFilesFromGlobArray(Array.isArray(patterns) ? patterns : [patterns]);
}

function parseScriptsFromBlades(files: FindFileResult[]): ScriptResult[] {
  return files.map((file) => {
    const bladeText = fs.readFileSync(file.fullpath, 'utf8');

    const html = parse(bladeText);
    // const key = file.relativePath.replace(/.blade.php$/, '').toLowerCase();

    return html.querySelectorAll('script[lang][data-macro]')
      .filter(
        (el) => ['ts', 'typescript'].includes(el.getAttribute('lang') || '')
      )
      .map((el) => ({
        as: el.getAttribute('data-macro') || '',
        file: file,
        path: file.relativePath.replace(/.blade.php$/, ''),
        code: el.innerHTML
      }))
      .filter((c) => c.code.trim() !== '');
  })
    .flat();
}

type CommentPlaceholder = { key: string; value: string; };

function stripComments(code: string): { code: string; comments: CommentPlaceholder[] } {
  const comments: CommentPlaceholder[] = [];
  let i = 0;

  code = code
    // Multi-line /* */
    .replace(/\/\*[\s\S]*?\*\//g, match => {
      const key = `__COMMENT_BLOCK_${i}__`;
      comments.push({ key, value: match });
      i++;
      return key;
    })
    // Single-line //
    .replace(/\/\/.*$/gm, match => {
      const key = `__COMMENT_LINE_${i}__`;
      comments.push({ key, value: match });
      i++;
      return key;
    });

  return { code, comments };
}

function restoreComments(code: string, comments: CommentPlaceholder[]): string {
  for (const { key, value } of comments) {
    const re = new RegExp(key, 'g');
    code = code.replace(re, value);
  }

  return code;
}
