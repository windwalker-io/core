let params = undefined;
export function prepareParams(p) {
    params = p;
    isVerbose = params?.verbose ? params?.verbose > 0 : false;
    return p;
}
let isVerbose = false;
const isProd = process.env.NODE_ENV === 'production';
const isDev = !isProd;
export { isVerbose, isDev, isProd, params };
//# sourceMappingURL=params.js.map