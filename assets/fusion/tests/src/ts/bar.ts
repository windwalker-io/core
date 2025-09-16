const yoo: {
  goo?: {
    koo: number;
    hoo: string;
  };
  joo: string;
} = {
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
