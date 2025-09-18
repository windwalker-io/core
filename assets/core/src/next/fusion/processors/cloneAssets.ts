import { callback, type ConfigBuilder } from '@windwalker-io/fusion-next';
import isGlob from 'is-glob';
import micromatch from 'micromatch';
import { normalize } from 'node:path';
import { relative } from 'node:path';
import { containsMiddleGlob, removeLastGlob, uniqId } from '../../utilities';

export function cloneAssets(patterns: Record<string, string>) {
  return callback((taskName, builder) => {
    const reposition = getAvailableForReposition(patterns);

    handleReposition(builder, reposition);

    handleCloneAssets(builder, Object.keys(patterns));

    return null;
  });
}

export function getAvailableForReposition(patterns: Record<string, string>) {
  const reposition: Record<string, string> = {};

  for (const from in patterns) {
    // If clone from contains middle glob: eg. `assets/**/files`, we cannot handle the naming
    // Let the naming options to handle it.
    if (!containsMiddleGlob(from)) {
      reposition[from] = patterns[from];
    }
  }

  return reposition;
}

export function handleCloneAssets(builder: ConfigBuilder, clonePatterns: string[]) {
  // An module starts `hidden:` will be ignored by fusion
  const id = uniqId('hidden:clone-asset-') + '.js';

  const task = builder.addTask(id);

  builder.resolveIdCallbacks.push((src) => {
    if (src === id) {
      return id;
    }
  });

  builder.loadCallbacks.push((src) => {
    if (src === id) {
      const glob = clonePatterns
        // Replace slash to unix style
        .map(v => v.replace(/\\/g, '/'))
        // Glob in virtual module should start with /
        .map(v => v.startsWith('./') || !v.startsWith('/') ? `/${v}` : v)
        // wrap with quotes
        .map(v => `'${v}'`)
        // join it to string.
        .join(', ');

      return `import.meta.glob(${glob});\n`;
    }
  });
}

export function handleReposition(builder: ConfigBuilder, reposition: Record<string, string>) {
  builder.assetFileNamesCallbacks.push((assetInfo) => {
    const fileName = assetInfo.originalFileName;

    for (const base in reposition) {
      if (match(fileName, base)) {
        return normalize(reposition[base] + relative(removeLastGlob(base), fileName)).replace(/\\/g, '/');
      }
    }
  });
}

function match(str: string, pattern: string) {
  if (isGlob(pattern)) {
    return micromatch.isMatch(str, pattern);
  }

  return str.startsWith(pattern);
}
