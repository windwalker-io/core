/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { src } from '../base/base.js';
import { postStream, prepareStream } from '../lifecycles.js';
import { extractDest, merge } from '../utilities/utilities.js';

export default class Processor {
  static defaultOptions = {};

  stream;
  source;
  options = {};

  constructor(source, options = {}) {
    this.source = source;

    this.options = options;

    this.prepareStream();
  }

  async prepareOptions(options = {}) {
    return options;
  }

  prepareStream() {
    if (this.stream) {
      return this;
    }

    this.stream = this.prepareSourceToStream(this.source, this.options);
    return this;
  }

  resetStream() {
    this.stream = null;

    return this;
  }

  prepareSourceToStream(source) {
    if (typeof source === 'string') {
      source = [source];
    }

    return prepareStream(this.createStream(source));
  }

  createStream(source) {
    return src(source, { follow: true });
  }

  async process(dest) {
    dest = extractDest(dest);

    this.doProcess(dest, await this.prepareOptions(this.options));

    return postStream(this.stream);
  }

  /**
   * Do process.
   * @param dest    {{merge: string, file: string, path: string, samePosition: boolean}}
   * @param options {object}
   */
  doProcess(dest, options = {}) {
    throw new Error('Please extends this method.');
  }

  /**
   *
   * @param handlers
   * @returns {this}
   */
  pipe(handlers) {
    if (!Array.isArray(handlers)) {
      handlers = [ handlers ];
    }

    handlers.forEach((handler) => {
      this.stream = this.stream.pipe(handler);
    });

    return this;
  }

  pipeIf(bool, callback) {
    if (typeof callback !== 'function') {
      throw new Error('pipeIf() needs 2nd argument is function.');
    }

    if (bool) {
      const r = callback();

      if (r) {
        this.pipe(callback());
      }
    }

    return this;
  }

  static setDefaultOptions(options) {
    this.defaultOptions = options;
  }
}
