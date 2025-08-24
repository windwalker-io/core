
export * from '@/dep';
import * as fusion from '@/dep';
import { params } from '@/runner';

const isVerbose = params.verbose ? params.verbose > 0 : false;

export { params, isVerbose };

export default {
  ...fusion,
  params,
};
