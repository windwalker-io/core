declare class MinifyOptions {
    NONE: string;
    SAME_FILE: string;
    SEPARATE_FILE: string;
}

type fusion_MinifyOptions = MinifyOptions;
declare const fusion_MinifyOptions: typeof MinifyOptions;
declare namespace fusion {
  export {
    fusion_MinifyOptions as MinifyOptions,
  };
}

export { MinifyOptions, fusion as default };
