export class CoreLoader {
  routes: Record<string, Function> = {};

  register(routes: Record<string, Function | string>) {
    for (const route in routes) {
      let target = routes[route];

      this.add(route, target);
    }

    return this;
  }

  add(route: string, target: Function | string) {
    if (typeof target === 'string') {
      target = () => import(`${target as string}`);
    }

    this.routes[route] = target;
  }

  remove(route: string) {
    delete this.routes[route];

    return this;
  }

  async import<T = any>(route: string): Promise<T> {
    const target = this.routes[route];

    if (!target) {
      throw new Error(`Unable to import file: ${route}, file not found.`);
    }

    return target();
  }

  reset() {
    this.routes = {};

    return this;
  }
}
