const path = require('path');
const glob = require('glob');

const stories = glob.sync(path.join(process.cwd(), 'styleguide', '**/*.stories.@(js|mdx)'));

const addons = [
  '@storybook/addon-actions',
  '@storybook/addon-essentials',
  '@storybook/addon-knobs',
  '@storybook/addon-links',
  '@storybook/addon-viewport',
];

const webpackFinal = (config) => {
  // Trick "babel-loader", force it to transpile @ecl-twig addons
  config.module.rules[0].exclude = /node_modules\/(?!@ecl-twig\/).*/;
  config.module.rules.push({
    test: /\.twig$/,
    loader: 'twing-loader',
    options: {
      environmentModulePath: path.resolve(__dirname, 'twing', 'environment.js'),
    },
  });
  config.plugins.forEach((plugin, i) => {
    if (plugin.constructor.name === 'ProgressPlugin') {
      config.plugins.splice(i, 1);
    }
  });

  return config;
};

module.exports = {
  stories,
  addons,
  webpackFinal,
  core: {
    builder: "webpack5",
  },
};
