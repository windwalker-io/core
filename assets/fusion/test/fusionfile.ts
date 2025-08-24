import { RollupOptions } from 'rollup';
import { css } from '../src';
import postcss from 'rollup-plugin-postcss';

enum Foo {
  A = 'a',
  B = 'b',
  C = 'c',
}

export async function cssTest() {
  return [
    css('./src/css/foo.css', './dest/foo.css'),
    css('./src/scss/foo.scss', './dest/foosass.css')
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

export async function hello(): Promise<any> {
  return [
    cssTest,
    world
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



export default [cssTest, hello];
