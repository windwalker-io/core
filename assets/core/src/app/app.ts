type Route = string | RouteLoader;
type RouteLoader = () => Promise<any>;

export class App {
  routes: Record<string, RouteLoader> = {};

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

  reset() {
    this.routes = {};

    return this;
  }
}
