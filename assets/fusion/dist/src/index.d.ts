export * from './dep';
import * as fusion from '@/dep';
declare const _default: {
    params: undefined;
    MinifyOptions: typeof fusion.MinifyOptions;
    css(input: import('./types').TaskInput, output: import('./types').TaskOutput, options?: import('./types').CssOptions): Promise<import('rollup').MaybeArray<import('vite').UserConfig>>;
    js(input: import('./types').TaskInput, output: import('./types').TaskOutput, options?: import('./types').JsOptions): Promise<import('vite').UserConfig[]>;
};
export default _default;
declare const isVerbose: boolean;
export { isVerbose };
