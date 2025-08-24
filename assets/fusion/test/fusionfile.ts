import { RollupOptions } from 'rollup';
import { css, js, MinifyOptions } from '../src';
import postcss from 'rollup-plugin-postcss';

enum Foo {
  A = 'a',
  B = 'b',
  C = 'c',
}

export async function cssTest() {
  return [
    css('./src/css/foo.css', './dest/foo.css', {
      browserslist: [
        // Simulate old browsers
        'since 2013',
      ],
    }),
    css(
      './src/scss/foo.scss',
      './dest/foosass.css',
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
    js('./src/js/foo.js', './dest/js/simple/'),
    js('./src/js/foo.js', {
      dir: './dest/js/simple/',
      entryFileNames: 'foo.bundle.js',
    }, { minify: MinifyOptions.SAME_FILE }),
    js(
      ['./src/js/single.js'],
      './dest/js/simple/single.umd.js',
      {
        format: 'umd',
        umdName: 'MySingle',
        minify: MinifyOptions.SEPARATE_FILE,
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
