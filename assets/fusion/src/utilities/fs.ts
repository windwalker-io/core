import { FileTasks } from '@/types';
import { isWindows } from '@/utilities/env.ts';
import fg from 'fast-glob';
import fs from 'fs-extra';
import { dirname, isAbsolute, relative, resolve } from 'node:path';
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

  const base = getBaseFromPattern(src);
  const sources = isGlob(src)
    ? fg.globSync(fg.convertPathToPattern(src), options.globOptions)
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

export async function symlink(target: string, link: string, force = false) {
  if (isWindows() && fs.lstatSync(target).isDirectory()) {
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

function getBaseFromPattern(pattern: string) {
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
