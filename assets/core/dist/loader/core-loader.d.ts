export declare class CoreLoader {
    routes: Record<string, Function>;
    register(routes: Record<string, Function | string>): this;
    add(route: string, target: Function | string): void;
    remove(route: string): this;
    import<T = any>(route: string): Promise<T>;
    reset(): this;
}
