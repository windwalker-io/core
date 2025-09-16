export type FileTask<T extends keyof FileTaskOptionTypes = 'none'> = { src: string; dest: string; options: FileTaskOptionTypes[T] };
export type FileTasks<T extends keyof FileTaskOptionTypes = 'none'> = FileTask<T>[];

type FileTaskOptionTypes = {
  'none': any,
  'move': any,
  'copy': any,
  'link': LinkOptions,
}

export interface LinkOptions {
  force?: boolean;
}
