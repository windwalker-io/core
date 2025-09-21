import { stripUrlQuery } from '@/next';
import { type ConfigBuilder, css, type ProcessorInterface, type ProcessorPreview } from '@windwalker-io/fusion-next';
import fg from 'fast-glob';
import fs from 'fs-extra';
import { parse } from 'node-html-parser';
import { normalize, resolve } from 'node:path';

export function cssModulize(entry: string, dest: string) {
  return new CssModulizeProcessor(css(entry, dest));
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

    for (const task of tasks) {
      builder.loadCallbacks.push((src, options) => {
        const file = stripUrlQuery(src);

        if (normalize(file) === resolve(task.input)) {
          const patterns = fg.globSync(
            this.cssPatterns.map((v) => resolve(v))
              .map(v => v.replace(/\\/g, '/'))
          );

          const imports = patterns
            .map((pattern) => `@import "${pattern}";`)
            .concat(parseStylesFromBlades(this.bladePatterns))
            .join('\n');

          let main = fs.readFileSync(file, 'utf-8');

          main += `\n\n${imports}\n`;

          return main;
        }
      });
    }

    return undefined;
  }

  preview(): ProcessorPreview[] {
    return [];
  }
}

function parseStylesFromBlades(patterns: string | string[]) {
  let files = fg.globSync(patterns);

  return files.map((file) => {
    const bladeText = fs.readFileSync(file, 'utf8');

    const html = parse(bladeText);

    return html.querySelectorAll('style[type],script[type]')
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
