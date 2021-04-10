/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { bar } from './bar.js';
import NoDeco from './no-deco.js';

@decorator('Hello')
export class Foo {
  flower = 'Sakura';
  static car = 'Tesla';

  constructor() {
    console.log(bar, NoDeco);
  }

  async getP() {
    if (bar) {
      return await p();
    }

    console.log('P');

    return '';
  }
}

function decorator(value) {
  return () => value;
}

async function p() {
  return 'p';
}
