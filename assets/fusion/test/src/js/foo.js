/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

@decorator('Hello')
export class Foo {
  flower = 'Sakura';
  static car = 'Tesla';

  constructor() {
    //
  }
}

function decorator(value) {
  return () => value;
}

// Test toplevel await
const bar = await import('./bar');
