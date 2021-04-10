/**
 * Part of fusion project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

export function babelBasicOptions() {
  const options = new BabelOptions();

  options.addPreset(
    '@babel/preset-env',
    {
      targets: 'last 3 version, safari 5, ie 10, not dead',
      modules: false
    }
  );
  options.addPlugin('@babel/plugin-proposal-decorators', { decoratorsBeforeExport: true });
  options.addPlugin('@babel/plugin-proposal-class-properties', { loose: true });
  options.addPlugin('@babel/plugin-proposal-optional-chaining');

  return options;
}

export function babelEmptyOptions() {
  return new BabelOptions();
}

export class BabelOptions {
  options = {
    presets: [],
    plugins: []
  };

  constructor(options = {}) {
  }

  reset() {
    this.options = {
      presets: [],
      plugins: []
    };
    return this;
  }

  resetPresets() {
    this.options.presets = [];
    return this;
  }

  resetPlugins() {
    this.options.presets = [];
    return this;
  }

  addPlugin(plugin, options = null) {
    if (typeof plugin === 'string' && options != null) {
      plugin = [plugin, options];
    }

    this.options.plugins.push(plugin);
    return this;
  }

  addPreset(preset, options = null) {
    if (typeof preset === 'string' && options != null) {
      preset = [preset, options];
    }

    this.options.presets.push(preset);
    return this;
  }

  get() {
    return this.options;
  }
}
