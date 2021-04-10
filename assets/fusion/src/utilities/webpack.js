/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

import { merge } from 'lodash-es';
import { babelBasicOptions } from './babel.js';

export async function webpackBasicConfig() {
  return {
    mode: process.env.NODE_ENV || 'development',
    output: {
      filename: '[name].js',
      sourceMapFilename: '[name].js.map'
    },
    stats: {
      all: false,
      errors: true,
      warnings: true,
      version: false,
    },
    module: {
      rules: [
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
  };
}

export async function webpackVue3Config() {
  const VueLoaderPlugin = await getVueLoader(3);

  return merge(await webpackBasicConfig(), {
    // devtool: 'eval-source-map',
    // ensure we are using the version of Vue that supports templates
    resolve: {
      alias: {
        'vue$': '@vue/runtime-dom',
        'vuex': 'vuex/dist/vuex.esm-bundler',
        '@': '.' // Will be overwrite when compile
      },
      extensions: ['*', '.js', '.vue', '.json', '.ts']
    },
    module: {
      rules: [
        {
          test: /\.scss$/,
          use: [
            'vue-style-loader',
            'css-loader',
            'sass-loader'
          ],
        },
        {
          test: /\.css$/,
          use: [
            'vue-style-loader',
            'css-loader'
          ],
        },
        {
          test: /\.vue$/,
          loader: 'vue-loader',
          options: {

          }
        },
        {
          test: /\.(png|jpg|gif|svg)$/,
          loader: 'file-loader',
          options: {
            name: '[name].[ext]?[hash]'
          }
        },
        {
          test: /\.ts$/,
          loader: "ts-loader",
          exclude: /(node_modules|bower_components)/,
          options:{
            appendTsSuffixTo:[/\.vue/]
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
    plugins: [
      new VueLoaderPlugin()
    ]
  });
}

export async function getVueLoader(version = 3) {
  try {
    const { VueLoaderPlugin } = (await import('vue-loader'));

    return VueLoaderPlugin;
  } catch (e) {
    const chalk = (await import('chalk')).default;
    console.error(chalk.red(e.message));
    console.error(`\nPlease run "${chalk.yellow('yarn add vue vue-loader vue-style-loader @vue/compiler-sfc css-loader sass-loader file-loader')}" first.\n`);
    process.exit(255);
  }
}
