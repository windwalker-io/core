/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */
import Sakura, { sakura } from './sakura';

export function flower() {
  return new Flower();
}

export default class Flower {
  content = null

  constructor(content = null) {
    this.content = content || new Sakura();
  }

  get() {
    return this.content || sakura();
  }
}
