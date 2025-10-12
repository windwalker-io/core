import { type ConfigBuilder, css, type ProcessorInterface, type ProcessorPreview } from '@windwalker-io/fusion-next';
import { WatchTask } from '@windwalker-io/fusion-next/src/types';
import fg from 'fast-glob';
import fs from 'fs-extra';
import { parse } from 'node-html-parser';
import { normalize, resolve } from 'node:path';
import { findModules, findPackages, stripUrlQuery } from '../../utilities';

export function cssModulize(entry: string, dest: string) {
  return new CssModulizeProcessor(css(entry, dest));
}

export interface CssModulizeDeepOptions {
  mergeCss?: boolean;
  parseBlades?: boolean;
}

export function cssModulizeDeep(stage: string, entry: string, dest: string, options: CssModulizeDeepOptions = {}) {
  const processor = cssModulize(entry, dest);

  if (options.mergeCss ?? true) {
    processor.mergeCss(findModules(`${stage}/**/assets/*.scss`));
  }

  if (options.parseBlades ?? true) {
    processor.parseBlades(
      findModules(`${stage}/**/*.blade.php`),
      findPackages('views/**/*.blade.php'),
    );
  }

  return processor;
}

class CssModulizeProcessor implements ProcessorInterface {

  constructor(
    protected processor: ReturnType<typeof css>,
    protected bladePatterns: string[] = [],
    protected cssPatterns: string[] = []
  ) {

  }

  parseBlades(...bladePatterns: (string[] | string)[]) {
    this.bladePatterns = this.bladePatterns.concat(bladePatterns.flat());

    return this;
  }

  mergeCss(...css: (string[] | string)[]) {
    this.cssPatterns = this.cssPatterns.concat(css.flat());

    return this;
  }

  config(taskName: string, builder: ConfigBuilder) {
    const tasks = this.processor.config(taskName, builder);
    const task = tasks[0];
    const inputFile = resolve(task.input);

    // get blade styles and add watches
    const bladeFiles = fg.globSync(this.bladePatterns);

    for (const file of bladeFiles) {
      builder.watches.push({
        file,
        moduleFile: inputFile,
        updateType: 'css-update',
      } satisfies WatchTask);
    }

    builder.loadCallbacks.push((src, options) => {
      const file = stripUrlQuery(src);

      if (normalize(file) === inputFile) {
        const patterns = fg.globSync(
          this.cssPatterns.map((v) => resolve(v))
            .map(v => v.replace(/\\/g, '/'))
        );

        const imports = patterns
          .map((pattern) => `@import "${pattern}";`)
          .concat(this.parseStylesFromBlades(bladeFiles))
          .join('\n');

        let main = fs.readFileSync(file, 'utf-8');

        main += `\n\n${imports}\n`;

        return main;
      }
    });

    return undefined;
  }

  parseStylesFromBlades(files: string[]) {
    return files.map((file) => {
      const bladeText = fs.readFileSync(file, 'utf8');

      const html = parse(bladeText);

      return html.querySelectorAll('style[type][data-macro],script[type][data-macro]')
        .filter(
          (el) => ['text/scss', 'text/css'].includes(el.getAttribute('type') || '')
        )
        .map((el) => {
          const scope = el.getAttribute('data-scope');

          if (scope) {
            return `${scope} {
          ${el.innerHTML}
        }`;
          } else {
            return el.innerHTML;
          }
        });
    })
      .filter((c) => c.length > 0)
      .flat();
  }

  preview(): ProcessorPreview[] {
    return [];
  }
}
