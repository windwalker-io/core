import { RollupOptions } from 'rollup';
import { css, js, MinifyOptions, params } from '../src';

enum Foo {
  A = 'a',
  B = 'b',
  C = 'c',
}

export async function cssTest() {
  return [
    css('./src/css/foo.css', './dest/css/foo.css', {
      browserslist: [
        // Simulate old browsers
        'since 2013',
      ],
      // minify: MinifyOptions.SAME_FILE,
    }),
    css(
      './src/scss/foo.scss',
      './dest/css/foosass.css',
      {
        minify: MinifyOptions.SEPARATE_FILE,
      }
    )
  ];

  // return [
  //   {
  //     input: './src/css/foo.css',
  //     output: {
  //       file: './dest/foo.css',
  //       format: 'es',
  //     },
  //     plugins: [
  //       postcss({
  //         extract: true,
  //         sourceMap: true
  //       }),
  //     ]
  //   },
  //   {
  //     input: './src/scss/foo.scss',
  //     output: {
  //       file: './dest/foosass.css',
  //       format: 'es',
  //     },
  //     plugins: [
  //       postcss({
  //         extract: true,
  //         sourceMap: true,
  //         use: ['sass']
  //       }),
  //     ]
  //   }
  // ];
}

export async function jsTest(): Promise<any> {
  return [
    js(
      './src/js/foo.js',
      './dest/js/simple/',
      {
        tsconfig: '../tsconfig.json',
        clean: true,
      }
    ),
    js(
      './src/js/foo.js',
      {
        dir: './dest/js/minify/',
        entryFileNames: 'foo.bundle.js',
      },
      {
        minify: MinifyOptions.SAME_FILE,
        tsconfig: '../tsconfig.json',
        clean: true,
      }),
    js(
      ['./src/js/single.js'],
      './dest/js/single/index.umd.js',
      {
        format: 'umd',
        umdName: 'MySingle',
        minify: MinifyOptions.SEPARATE_FILE,
        tsconfig: '../tsconfig.json',
        clean: true,
      }
    )
  ];
}

export async function tsTest(): Promise<any> {
  return [
    js(
      './src/ts/foo.ts',
      './dest/ts/simple/',
      {
        tsconfig: '../tsconfig.json',
      }
    ),
    js(
      './src/ts/foo.ts',
      {
        dir: './dest/ts/minify/',
        entryFileNames: 'foo.bundle.js',
      },
      {
        minify: MinifyOptions.SAME_FILE,
        tsconfig: '../tsconfig.json',
      }),
    js(
      ['./src/ts/single.ts'],
      './dest/ts/single/index.umd.js',
      {
        format: 'umd',
        umdName: 'MySingle',
        minify: MinifyOptions.SEPARATE_FILE,
        tsconfig: '../tsconfig.json',
      }
    )
  ];
}

export async function world(): Promise<RollupOptions[]> {
  return [
    {
      input: './src/css/world.css',
      output: {
        dir: './dest/css/moved/',
        format: 'es',
      },
    },
    {
      input: './src/css/world.scss',
      output: {
        dir: './dest/css/moved/',
        format: 'es',
      },
    }
  ];
}

export default [cssTest];
