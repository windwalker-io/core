import path from 'path';
import { postTask } from '@windwalker-io/fusion';
import { cliInput } from '@windwalker-io/fusion/src/utilities/cli.js';
import { webpackBasicConfig } from '../utilities/webpack.js';

export async function webpackBundle(file, dest, override = null) {

  let webpack;

  try {
    webpack = (await import('webpack')).default;
  } catch (e) {
    const chalk = (await import('chalk')).default;
    console.error(chalk.red(e.message));
    console.error(
      `\nPlease run "${chalk.yellow('yarn add webpack webpack-stream webpack-comment-remover-loader ' +
        'babel-loader css-loader sass-loader style-loader postcss-loader')}" first.\n`
    );
    process.exit(255);
    return;
  }

  const config = await webpackBasicConfig();

  if (dest.endsWith('/')) {
    dest += path.basename(file);
  }

  config.entry = path.resolve(file);
  config.output.path = path.dirname(path.resolve(dest));
  config.output.filename = path.basename(dest);
  config.output.uniqueName = file;
  config.output.libraryTarget = 'umd';

  config.resolve.modules = ['node_modules'];

  if (override) {
    override(config);
  }

  const compiler = webpack(config);

  const statsOptions = {
    colors: true,
    modules: false,
    children: false,
    chunks: false,
    chunkModules: false
  };

  return new Promise((resolve) => {
    if (cliInput['watching']) {
      const watching = compiler.watch(
        {},
        (err, states) => {
          console.log(states.toString(statsOptions));
          postTask();
        }
      );

      resolve();
      return;
    }

    compiler.run((err, states) => {
      console.log(states.toString(statsOptions));
      compiler.close(() => {});
      postTask();

      resolve();
    });
  });
}

