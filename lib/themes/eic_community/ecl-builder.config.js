const path = require('path');

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
      entry: path.resolve(__dirname, 'sass/style-ec.scss'),
      dest: path.resolve(outputFolder, 'css/style-ec.css'),
      options: style_options,
    }
  ],
  sprites: {
    entry: {
      custom: `./images/sprite/custom`,
      branded: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/branded',
      'eic-branded': './images/sprite/eic-branded',
      general: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/general',
      notifications: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/notifications',
      ui: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/ui',
      fields_of_science: `./images/fields-of-science`,
    },
    name: 'custom',
    destination: 'dist/images/sprite',
  },
  copy: [
    { from: path.resolve(nodeModules, '@ecl/ec-preset-editor/dist'), to: path.resolve(outputFolder, 'dist') },
    { from: path.resolve(nodeModules, '@ecl/preset-reset/dist'), to: path.resolve(outputFolder, 'dist/preset-reset') },
    { from: path.resolve(nodeModules, '@ecl/preset-ec/dist'), to: path.resolve(outputFolder, 'dist/ec') },
    { from: path.resolve(nodeModules, '@ecl/preset-eu/dist'), to: path.resolve(outputFolder, 'dist/eu') },
    { from: path.resolve(nodeModules, 'svg4everybody/dist'), patterns: 'svg4everybody.js', to: path.resolve(outputFolder, 'dist/js') },
    { from: path.resolve(nodeModules, 'pikaday'), patterns: 'pikaday.js', to: path.resolve(outputFolder, 'dist/js') },
    { from: path.resolve(nodeModules, 'moment/min'), patterns: 'moment.min.js', to: path.resolve(outputFolder, 'dist/js') },
    { from: path.resolve(__dirname, 'images/fields-of-science'), to: path.resolve(outputFolder, 'dist/images/fields-of-science') },
    { from: path.resolve(__dirname, 'images/other'), to: path.resolve(outputFolder, 'dist/images/other') },
    { from: path.resolve(nodeModules, 'flag-icons/flags/flags-iso/flat'), to: path.resolve(outputFolder, 'dist/images/flags') },
  ]
};
