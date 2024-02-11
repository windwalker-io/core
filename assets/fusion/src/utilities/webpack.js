/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { babelBasicOptions } from './babel.js';
import webpack from 'webpack';

const majorVer = Number(webpack.version.split('.').shift());

export async function webpackBasicConfig() {
  const devtool = process.env.WEBPACK_DEVTOOL || (process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-cheap-module-source-map');

  return processByVersion(
    {
      mode: process.env.NODE_ENV || 'development',
      output: {
        filename: '[name].js',
        sourceMapFilename: '[name].js.map'
      },
      resolve: {
        extensions: ['.js', '.vue', '.json', '.ts']
      },
      experiments: {
        topLevelAwait: true,
      },
      devtool,
      stats: {
        all: false,
        errors: true,
        warnings: true,
        version: false,
      },
      module: {
        rules: [
          {
            test: /\.scss$/,
            use: [
              'style-loader',
              'css-loader',
              postCSSLoader(),
              'sass-loader',
            ],
          },
          {
            test: /\.css$/,
            use: [
              'style-loader',
              'css-loader',
              postCSSLoader()
            ],
          },
          {
            test: /\.ts$/,
            loader: "ts-loader",
            exclude: /(node_modules|bower_components)/,
            options:{
              // appendTsSuffixTo:[/\.vue/],
              transpileOnly: true
            }
          },
          {
            test: /\.m?js$/,
            exclude: /(node_modules|bower_components)/,
            use: [{
              loader: 'babel-loader',
              options: babelBasicOptions().get()
            }, 'webpack-comment-remover-loader']
          }
        ]
      },
      plugins: []
    }
  );
}

export async function webpackVue3Config() {
  const VueLoaderPlugin = await getVueLoader(3);
  const devtool = process.env.WEBPACK_DEVTOOL || (process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-cheap-source-map');

  return processByVersion(
    {
      mode: process.env.NODE_ENV || 'development',
      output: {
        filename: '[name].js',
        chunkFilename: '[name]-[chunkhash].js',
        sourceMapFilename: '[name].js.map',
      },
      stats: {
        all: false,
        errors: true,
        warnings: true,
        version: false,
      },
      experiments: {
        topLevelAwait: true,
      },
      devtool,
      // ensure we are using the version of Vue that supports templates
      resolve: {
        alias: {
          'vue$': '@vue/runtime-dom',
          'vuex': 'vuex/dist/vuex.esm-bundler',
          '@': '.' // Will be overwrite when compile
        },
        extensions: ['.js', '.vue', '.json', '.ts']
      },
      module: {
        rules: [
          {
            test: /\.scss$/,
            use: [
              'vue-style-loader',
              'css-loader',
              postCSSLoader(),
              'sass-loader'
            ],
          },
          {
            test: /\.css$/,
            use: [
              'vue-style-loader',
              'css-loader',
              postCSSLoader()
            ],
          },
          {
            test: /\.vue$/,
            loader: 'vue-loader',
            options: {
              compilerOptions: {
                whitespace: 'preserve'
              }
            }
          },
          {
            test: /\.(png|jpg|gif|svg)$/,
            loader: 'file-loader',
            options: {
              name: '[name].[ext]?[hash]'
            }
          },
          // {
          //   // Match `.js`, `.jsx`, `.ts` or `.tsx` files
          //   test: /\.[jt]sx?$/,
          //   loader: 'esbuild-loader',
          //   options: {
          //     // JavaScript version to compile to
          //     target: 'esnext',
          //     loader: 'ts',
          //     // appendTsSuffixTo:[/\.vue/],
          //   }
          // },
          {
            test: /\.ts$/,
            loader: "ts-loader",
            exclude: /(node_modules|bower_components)/,
            options:{
              appendTsSuffixTo:[/\.vue/],
              transpileOnly: true
            }
          },
          {
            test: /\.m?js$/,
            exclude: /(node_modules|bower_components)/,
            loader: 'babel-loader',
            options: babelBasicOptions().get()
          }
        ]
      },
      // optimization: {
      //   minimizer: [
      //     new EsbuildPlugin({
      //       target: 'esnext'
      //     })
      //   ]
      // },
      plugins: [
        new VueLoaderPlugin(),
      ]
    }
  );
}

export async function getVueLoader(version = 3) {
  try {
    const { VueLoaderPlugin } = (await import('vue-loader'));

    return VueLoaderPlugin;
  } catch (e) {
    const chalk = (await import('chalk')).default;
    console.error(chalk.red(e.message));
    console.error(
      `\nPlease run "${chalk.yellow('yarn add vue@^3.0 vue-loader@^16.0 vue-style-loader ' +
        '@vue/compiler-sfc file-loader')}" first.\n`
    );
    process.exit(255);
  }
}

function postCSSLoader() {
  return {
    loader: "postcss-loader",
    options: {
      postcssOptions: {
        // @see https://github.com/postcss/postcss/issues/1375#issuecomment-673865735
        hideNothingWarning: true,
      },
    },
  };
}

function processByVersion(options) {
  if (majorVer < 5) {
    delete options.experiments;
  }

  return options;
}
