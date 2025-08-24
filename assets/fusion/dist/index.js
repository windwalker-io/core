import * as fusion from '@/dep';
export * from '@/dep';
import { params } from '@/runner';
export { params } from '@/runner';

const isVerbose = params.verbose ? params.verbose > 0 : false;
var index = {
    ...fusion,
    params,
};

export { index as default, isVerbose };
//# sourceMappingURL=index.js.map
