import fs from 'fs';
import isGlob from 'is-glob';

export function loadJson(file) {
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
