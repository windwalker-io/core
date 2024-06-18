export class CoreLoader {
  routes = {};

  /**
   * @param {Record<string, Function | string>} routes
   * @returns {CoreLoader}
   */
  register(routes) {
    for (const route in routes) {
      let target = routes[route];

      this.add(route, target);
    }

    return this;
  }

  /**
   * @param {string} route
   * @param {string | Function} target
   * @returns {CoreLoader}
   */
  add(route, target) {
    if (typeof target === 'string') {
      target = () => import(target);
    }

    this.routes[route] = target;

    return this;
  }

  /**
   * @param {string} route
   * @returns {CoreLoader}
   */
  remove(route) {
    delete this.routes[route];

    return this;
  }

  /**
   * @param {string} route
   * @returns {Promise<*>}
   */
  async import(route) {
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

export const loader = new CoreLoader();
