
// @ts-ignore
@decorator('Hello')
// @ts-ignore
export class Foo {
  flower: string = 'Sakura';
  static car: string = 'Tesla';

  constructor() {
    //
  }
}

function decorator(value: Foo): Function {
  console.log(value);
  return () => value;
}

// Test toplevel await
const bar = await import('./bar');

export const foo = new Foo();
