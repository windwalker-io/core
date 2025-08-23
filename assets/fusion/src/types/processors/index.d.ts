export type ProcessSource = string | string[];
export type ProcessDest = string | string[];

export interface OptionsCollection {
  css: CssOptions;
}

export type ProcessorOptions<T> = OptionsCollection[T];

export * from './css.d.ts';
