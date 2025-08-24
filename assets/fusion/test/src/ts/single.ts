
export default class Single {
  constructor() {
    console.log('Single instance created');
  }

  greet(): void {
    const greet: string = 'Hello from Single instance';

    console.log(greet);
  }
}

export const single = new Single() satisfies Single;
