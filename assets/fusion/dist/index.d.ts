import { InputOption, OutputOptions, RollupOptions } from 'rollup';

declare class MinifyOptions {
    NONE: string;
    SAME_FILE: string;
    SEPARATE_FILE: string;
}

type TaskInput = InputOption;
type TaskOutput = OutputOptions | string;
type ExtraOptions = Partial<RollupOptions> | ((options: Partial<RollupOptions>) => Partial<RollupOptions> | undefined);

declare function css(input: TaskInput, output: TaskOutput, options?: ExtraOptions): Promise<RollupOptions>;

type fusion_MinifyOptions = MinifyOptions;
declare const fusion_MinifyOptions: typeof MinifyOptions;
declare const fusion_css: typeof css;
declare namespace fusion {
  export {
    fusion_MinifyOptions as MinifyOptions,
    fusion_css as css,
  };
}

export { MinifyOptions, css, fusion as default };
