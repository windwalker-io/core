export * from '@/processors';
export { isVerbose, isDev, isProd, params } from '@/params';

export { isWindows } from '@/utilities/env';
export { shortHash } from '@/utilities/crypto';
export { copyGlob, moveGlob, symlink, fileToId } from '@/utilities/fs';

export type {
  FusionPlugin,
  MaybePromise,
  MaybeArray,
} from '@/types';
export type { default as BuildTask } from '@/builder/BuildTask.ts';
export type { default as ConfigBuilder } from '@/builder/ConfigBuilder.ts';
