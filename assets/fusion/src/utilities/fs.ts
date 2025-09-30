import { FileTasks } from '../types';
import { isWindows } from '../utilities/env.ts';
import { shortHash } from '../utilities/crypto.ts';
import fg from 'fast-glob';
import fs from 'fs-extra';
import { randomBytes } from 'node:crypto';
import { dirname, isAbsolute, normalize, relative, resolve } from 'node:path';
import { Logger } from 'vite';

function handleFilesOperation(
  src: string,
  dest: string,
  options: {
    outDir: string;
    handler: (src: string, dest: string) => Promise<any>;
    globOptions?: fg.Options;
  }
) {
  const promises = [];
  src = normalizeFilePath(src, options.outDir);
  dest = normalizeFilePath(dest, options.outDir);

  const base = getGlobBaseFromPattern(src);
  const sources = isGlob(src)
    ? fg.globSync(src.replace(/\\/g, '/'), options.globOptions)
    : [src];

  for (let source of sources) {
    let dir;
    let resolvedDest = dest;

    if (endsWithSlash(dest)) {
      dir = resolvedDest;
      resolvedDest = resolvedDest + relative(base, source);
    } else {
      dir = dirname(resolvedDest);
    }

    fs.ensureDirSync(dir);

    promises.push(options.handler(source, resolvedDest));
  }

  return promises;
}

export function moveFilesAndLog(tasks: FileTasks, outDir: string, logger: Logger) {
  const promises = [];

  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src, dest) => {
          logger.info(`Moving file from ${relative(outDir, src)} to ${relative(outDir, dest)}`);
          return fs.move(src, dest, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );

    promises.push(...ps);
  }

  return Promise.all(promises);
}

export function copyFilesAndLog(tasks: FileTasks, outDir: string, logger: Logger) {
  const promises = [];

  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest, {
        outDir,
        handler: async (src, dest) => {
          logger.info(`Copy file from ${relative(outDir, src)} to ${relative(outDir, dest)}`);
          return fs.copy(src, dest, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );

    promises.push(...ps);
  }

  return Promise.all(promises);
}

export function linkFilesAndLog(tasks: FileTasks<'link'>, outDir: string, logger: Logger) {
  const promises = [];

  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest, {
        outDir,
        handler: async (src, dest) => {
          logger.info(`Link file from ${relative(outDir, src)} to ${relative(outDir, dest)}`);
          return symlink(src, dest, options?.force ?? false);
        },
        globOptions: { onlyFiles: false }
      }
    );

    promises.push(...ps);
  }

  return Promise.all(promises);
}

export function cleanFiles(patterns: string[], outDir: string) {
  const promises = [];

  outDir = outDir.replace(/\\/g, '/');

  for (let src of patterns) {
    src = normalizeFilePath(src, outDir);
    src = resolve(src);
    
    const sources = isGlob(src)
      ? fg.globSync(src.replace(/\\/g, '/'), { onlyFiles: false })
      : [src];

    // To protect `upload/*` folder.
    const protectDir = resolve(outDir + '/upload').replace(/\\/g, '/');

    for (let source of sources) {
      if (source.replace(/\\/g, '/').startsWith(protectDir)) {
        throw new Error('Refuse to delete `upload/*` folder.');
      }

      promises.push(fs.remove(source));
    }
  }

  return Promise.all(promises);
}

export async function copyGlob(src: string, dest: string): Promise<void> {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src, dest) => fs.copy(src, dest, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );

  await Promise.all(promises);
}

export async function moveGlob(src: string, dest: string): Promise<void> {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src, dest) => fs.move(src, dest, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );

  await Promise.all(promises);
}

export async function symlink(target: string, link: string, force = false) {
  target = resolve(target);
  link = resolve(link);

  if (isWindows() && !fs.lstatSync(target).isFile()) {
    return fs.ensureSymlink(target, link, 'junction');
  }

  if (isWindows() && fs.lstatSync(target).isFile() && force) {
    return fs.ensureLink(target, link);
  }

  return fs.ensureSymlink(target, link);
}

export function endsWithSlash(path: string): boolean {
  return path.endsWith('/') || path.endsWith('\\');
}

export function getGlobBaseFromPattern(pattern: string) {
  const specialChars = ["*", "?", "[", "]"];
  const idx = [...pattern].findIndex(c => specialChars.includes(c));

  if (idx === -1) {
    return dirname(pattern);
  }

  return dirname(pattern.slice(0, idx + 1));
}

function isGlob(pattern: string): boolean {
  const specialChars = ["*", "?", "[", "]"];
  return specialChars.some(c => pattern.includes(c));
}

function normalizeFilePath(path: string, outDir: string) {
  if (path.startsWith('.')) {
    path = resolve(path);
  } else if (!isAbsolute(path)) {
    path = outDir + '/' + path;
  }

  return path;
}

export function fileToId(input: string, group?: string) {
  input = normalize(input);

  group ||= randomBytes(4).toString('hex');

  return group + '-' + shortHash(input);
}
