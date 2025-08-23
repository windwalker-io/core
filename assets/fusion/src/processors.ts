import { CssOptions, ProcessDest, ProcessSource } from './types/index';

export async function css(source: ProcessSource, dest: ProcessDest, options: CssOptions = {}) {
  const CssProcessor = (await import('./processors/css-processor.ts')).default;

  return new CssProcessor(source, options).process(dest);
}

