/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

// @ts-ignore
import Bar, { decorator } from './bar';

// @ts-ignore
@decorator
// @ts-ignore
export class Foo {
  flower: string = 'Sakura';
  static car: string = 'Tesla';

  constructor() {
    //
  }
}

function decorator(value: Foo): Function {
  return () => value;
}
