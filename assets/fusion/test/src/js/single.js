
export default class Single {
  constructor() {
    console.log('Single instance created');
  }

  greet() {
    console.log('Hello from Single instance');
  }
}

export const single = new Single();
