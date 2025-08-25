import { existsSync } from 'node:fs';
import { isAbsolute, resolve } from 'node:path';
import { MaybeArray, MaybePromise, RollupOptions } from 'rollup';
import { ConfigResult, LoadedConfigTask, RunnerCliParams } from './types';

export async function loadConfigFile(configFile: ConfigResult): Promise<Record<string, LoadedConfigTask>> {
  let path = configFile.path;

  // If is Windows, Add "file://" prefix to path
  if (process.platform === 'win32') {
    // Replace backslash to slash
    const winPath = path.replace(/\\/g, '/');
    // Add file:// prefix if not exists
    if (!winPath.startsWith('file://')) {
      // Add extra slash to make it absolute path
      // e.g. C:/path/to/file
      // becomes file:///C:/path/to/file
      path = `file:///${winPath}`;
    }
  }

  const modules = await import(path);

  return { ...modules };
}

export async function resolveTaskOptions(task: LoadedConfigTask, resolveSubFunctions = false): Promise<RollupOptions[]> {
  if (!resolveSubFunctions && Array.isArray(task)) {
    const results = await Promise.all(task.map((task) => resolveTaskOptions(task, true)));
    return results.flat();
  }

  if (typeof task === 'function') {
    return resolvePromisesToFlatArray(await task());
  }

  return resolvePromisesToFlatArray((await task) as MaybeArray<RollupOptions>);
}

async function resolvePromisesToFlatArray(tasks: MaybeArray<MaybePromise<RollupOptions>>) {
  if (!Array.isArray(tasks)) {
    return [await tasks];
  }

  const resolvedTasks = await Promise.all(tasks);
  const returnTasks = [];

  for (const resolvedTask of resolvedTasks) {
    if (Array.isArray(resolvedTask)) {
      returnTasks.push(...resolvedTask);
    } else {
      returnTasks.push(resolvedTask);
    }
  }

  return returnTasks;
}

export function mustGetAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult {
  const found = getAvailableConfigFile(root, params);

  if (!found) {
    throw new Error('No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.');
  }

  return found;
}

export function getAvailableConfigFile(root: string, params: RunnerCliParams): ConfigResult | null {
  let found = params?.config;

  if (found) {
    // If is not absolute of system path(consider Windows), prepend root
    if (!isAbsolute(found)) {
      found = resolve(root, found);
    }

    if (existsSync(found)) {
      return {
        path: found,
        // get filename from file path
        filename: found.split('/').pop() || '',
        type: getConfigModuleType(found),
        ts: isConfigTypeScript(found),
      };
    }

    return null;
  }

  return findDefaultConfig(root);
}

export function findDefaultConfig(root: string): ConfigResult | null {
  let file = resolve(root, 'fusionfile.js');

  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split('/').pop() || '',
      type: 'commonjs',
      ts: false,
    };
  }

  file = resolve(root, 'fusionfile.mjs');

  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split('/').pop() || '',
      type: 'module',
      ts: false,
    };
  }

  file = resolve(root, 'fusionfile.ts');

  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split('/').pop() || '',
      type: 'module',
      ts: true,
    };
  }

  file = resolve(root, 'fusionfile.mts');

  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split('/').pop() || '',
      type: 'module',
      ts: true,
    };
  }

  return null;
}

function getConfigModuleType(file: string) {
  let type: 'commonjs' | 'module' | 'unknown' = 'unknown';

  if (file.endsWith('.cjs')) {
    type = 'commonjs';
  } else if (file.endsWith('.mjs')) {
    type = 'module';
  } else if (file.endsWith('.ts') || file.endsWith('.mts')) {
    type = 'module';
  }

  return type;
}

function isConfigTypeScript(file: string) {
  return file.endsWith('.ts') || file.endsWith('.mts');
}
