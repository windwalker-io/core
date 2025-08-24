import postcss from 'rollup-plugin-postcss';

class MinifyOptions {
    NONE = 'none';
    SAME_FILE = 'same_file';
    SEPARATE_FILE = 'separate_file';
}

async function css(input, output, options) {
    if (typeof output === 'string') {
        if (output.endsWith('/')) {
            output = {
                dir: output,
                format: 'es',
            };
        }
        else {
            output = {
                file: output,
                format: 'es',
            };
        }
    }
    try {
        const { vue } = await import('rollup-plugin-vue');
    }
    catch (e) {
        console.log(e);
    }
    let opt = {
        input,
        output,
        plugins: [
            postcss({
                extract: true,
                sourceMap: true,
                use: ['sass']
            }),
        ],
    };
    if (typeof options === 'function') {
        opt = options(opt) ?? opt;
    }
    else {
        opt = { ...opt, ...options };
    }
    return opt;
}

var fusion = /*#__PURE__*/Object.freeze({
    __proto__: null,
    MinifyOptions: MinifyOptions,
    css: css
});

export { MinifyOptions, css, fusion as default };
//# sourceMappingURL=index.js.map
