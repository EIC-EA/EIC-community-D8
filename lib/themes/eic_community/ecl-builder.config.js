const path = require('path');
const pkg = require('./package.json');

const isProd = process.env.NODE_ENV === 'production';
const outputFolder = __dirname;

const nodeModules = __dirname + '/node_modules';

// SCSS includePaths
const includePaths = [nodeModules];

const style_options = {
  includePaths,
  sourceMap: isProd ? 'none' : true,
};

module.exports = {
  styles: [
    {
      entry: path.resolve(__dirname, 'sass/eic_community.screen.scss'),
      dest: path.resolve(outputFolder, 'dist/css/eic_community.screen.css'),
      options: style_options,
    },
    {
      entry: path.resolve(__dirname, 'sass/eic_community.print.scss'),
      dest: path.resolve(outputFolder, 'dist/css/eic_community.print.css'),
      options: style_options,
    },
  ],
  copy: [
    { from: path.resolve(nodeModules, '@ecl/ec-preset-editor/dist'), to: path.resolve(outputFolder, 'dist') },
    { from: path.resolve(nodeModules, '@ecl/ec-preset-legacy-website/dist'), to: path.resolve(outputFolder, 'dist') },
    { from: path.resolve(nodeModules, 'svg4everybody/dist'), patterns: 'svg4everybody.min.js', to: path.resolve(outputFolder, 'dist/scripts') },
  ]
};
