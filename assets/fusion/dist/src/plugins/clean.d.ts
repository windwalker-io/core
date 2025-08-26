import { MaybePromise, OutputOptions, Plugin } from 'rollup';
export type CleanHandler = boolean | ((dir: string, outputOptions: OutputOptions) => MaybePromise<void>);
export default function clean(handler: CleanHandler, verbose?: boolean): Plugin;
