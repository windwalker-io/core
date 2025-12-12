type Route = string | RouteLoader;
type RouteLoader = () => Promise<any>;

let currentProps: Record<string, any> | null = null;

export class App {
  routes: Record<string, RouteLoader> = {};
  queue: (() => Promise<any>)[] = [];
  queueRunning = false;

  constructor(routes: Record<string, Route> = {}) {
    this.registerRoutes(routes);
  }

  registerRoutes(routes: Record<string, Route>) {
    for (const route in routes) {
      this.addRoute(route, routes[route]);
    }

    return this;
  }

  addRoute(route: string, target: Route) {
    if (typeof target === 'string') {
      target = () => import(/* @vite-ignore */`${target as string}`);
    }

    this.routes[route] = target;

    return this;
  }

  removeRoute(route: string) {
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

  async importSync<T = any>(route: string, props: Record<string, any> = {}): Promise<T> {
    return new Promise<any>((resolve) => {
      const target = this.routes[route];

      if (!target) {
        throw new Error(`Unable to import file: ${route}, file not found.`);
      }

      this.queue.push(async () => {
        currentProps = props;
        resolve(await target());
      });

      this.runQueue();
    });
  }

  async runQueue() {
    if (!this.queueRunning) {
      this.queueRunning = true;
      let item: () => any;

      while (item = this.queue.shift()) {
        await item();
      }

      this.queueRunning = false;
    }
  }

  reset() {
    this.routes = {};

    return this;
  }
}

export function useMacroProps<T extends Record<string, any> = Record<string, any>>(): T {
  if (currentProps == null) {
    throw new Error('Cannot get macro props.');
  }
  
  return { ...currentProps } as T;
}
