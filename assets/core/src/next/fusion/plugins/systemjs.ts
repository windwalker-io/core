import fs from 'fs-extra';
import { resolve } from 'node:path';
import type { OutputAsset, OutputChunk } from 'rollup';
import type { PluginOption } from 'vite';

export function injectSystemJS(systemPath?: string, filter?: (file: OutputAsset | OutputChunk) => any): PluginOption {
  systemPath ??= resolve('node_modules/systemjs/dist/system.min.js');

  return {
    name: 'inject-systemjs',
    async generateBundle(options, bundle) {
      if (options.format !== 'system') {
        return;
      }

      const systemjsCode = fs.readFileSync(
        resolve(systemPath),
        'utf-8'
      );

      for (const file of Object.values(bundle)) {
        if (filter && !filter(file)) {
          continue;
        }

        if (file.type === 'chunk' && file.isEntry && file.fileName.endsWith('.js')) {
          file.code = systemjsCode + '\n' + file.code;
        }
      }
    }
  };
}

export function systemCSSFix(): PluginOption {
  return {
    name: 'systemjs.css.fix',
    async generateBundle(options, bundle) {
      if (options.format !== 'system') {
        return;
      }

      for (const [fileName, chunk] of Object.entries(bundle)) {
        if (fileName.endsWith('.css') && 'code' in chunk) {
          const regex = /__vite_style__\.textContent\s*=\s*"([\s\S]*?)";/;
          const match = chunk.code.match(regex);

          if (match && match[1]) {
            chunk.code = match[1]
              .replace(/\\"/g, '"')
              .replace(/\\n/g, '\n')
              .replace(/\\t/g, '\t')
              .replace(/\\\\/g, '\\')
              .replace(/\/\*\$vite\$:\d+\*\/$/, '')
          }
        }
      }
    }
  };
}

