import { TransformOptions } from '@babel/core';
import { WatchMethod } from 'gulp';

declare namespace FusionUtilitiesBabel {
  // export interface BabelOptionsBuilder {
  //   options: TransformOptions;
  //
  //   new(options?: TransformOptions);
  //
  //   get(): TransformOptions;
  //
  //   addPreset(plugin: string | Array<any> | object, options?: object): this;
  //
  //   addPlugin(plugin: string | Array<any> | object, options?: object): this;
  // }

  export class BabelOptions {
    options: TransformOptions;

    constructor(options?: TransformOptions);

    get(): TransformOptions;

    addPreset(plugin: string | Array<any> | object, options?: object): this;

    addPlugin(plugin: string | Array<any> | object, options?: object): this;

    reset(): this;

    resetPresets(): this;

    resetPlugins(): this;
  }

  export function babelEmptyOptions(): BabelOptions;
  export function babelBasicOptions(): BabelOptions;
}

export = FusionUtilitiesBabel;

// declare const Fusion: Fusion;
// export = Fusion;
