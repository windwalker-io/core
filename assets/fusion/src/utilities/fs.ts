import { move } from 'fs-extra';
import { isAbsolute, relative, resolve } from 'node:path';
import { createLogger, Logger } from 'vite';

export function moveFilesAndLog(files: Record<string, string>, outDir: string, logger: Logger) {
  const promises = [];

  for (let src in files) {
    let dest = files[src];

    src = normalizeFilePath(src, outDir);
    dest = normalizeFilePath(dest, outDir);

    logger.info(`Moving file from ${relative(outDir, src)} to ${relative(outDir, dest)}`);

    promises.push(move(src, dest, { overwrite: true }));
  }

  return Promise.all(promises);
}

function normalizeFilePath(path: string, outDir: string) {
  if (path.startsWith('.')) {
    path = resolve(path);
  } else if (!isAbsolute(path)) {
    path = outDir + '/' + path;
  }

  return path;
}
