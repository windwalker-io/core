import { getGlobBaseFromPattern } from '@windwalker-io/fusion-next';
import fs from 'node:fs';
import fg from 'fast-glob';
import isGlob from 'is-glob';
import { relative } from 'node:path';

export function loadJson(file: string) {
  if (!fs.existsSync(file)) {
    return null;
  }

  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

export function containsMiddleGlob(str: string) {
  return isGlob(removeLastGlob(str));
}

export function removeLastGlob(str: string) {
  // Remove `/**` `/*` `/**/*` at the end of the string
  return str.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, '');
}

const ds = process.platform === 'win32' ? '\\' : '/';

export function ensureDirPath(path: string, slash: '/' | '\\' = ds): string {
  if (!path.endsWith(slash)) {
    return path + slash;
  }

  return path;
}

export interface FindFileResult {
  fullpath: string;
  relativePath: string;
}

export function findFilesFromGlobArray(sources: string[]): FindFileResult[] {
  let files: FindFileResult[] = [];

  for (const source of sources) {
    files = [
      ...files,
      ...findFiles(source)
    ];
  }

  return files;
}

function findFiles(src: string): FindFileResult[] {
  return fg.globSync(src).map((file: string) => {
    file = file.replace(/\\/g, '/');

    return {
      fullpath: file,
      relativePath: relative(getGlobBaseFromPattern(src), file).replace(/\\/g, '/')
    };
  });
}
