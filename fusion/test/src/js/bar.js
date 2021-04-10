/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

const yoo = {
  goo: {
    koo: 567,
    hoo: 'World',
  },
  joo: '123'
};

export const bar = `Hello ${yoo?.goo?.hoo}`;

// Test toplevel await
const foo = await import('./foo');

console.log(foo);
