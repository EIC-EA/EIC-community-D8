const path = require('path');
const pkg = require('./package.json');
const { sync } = require('glob');
const dotenv = require('dotenv');

const environment = dotenv.config();
if (process.env && !process.env.NODE_ENV) {
  process.env.NODE_ENV = 'production';
}

const isProd = process.NODE_ENV === 'production';
const nodeModules = __dirname + '/node_modules';

// SCSS includePaths
const includePaths = [nodeModules];

const script_options = {
  sourceMap: isProd ? false : 'inline',
};

const style_options = {
  includePaths,
  sourceMap: isProd ? 'none' : true,
  outputStyle: isProd ? 'compressed' : 'compact',
};

const config = {
  src: __dirname,
  dest: path.resolve(__dirname, 'dist'),
  sprites: {
    entry: {
      custom: `./images/sprite/custom`,
      branded: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/branded',
      general: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/general',
      notifications:
        './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/notifications',
      ui: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/ui',
      fields_of_science: `./images/fields-of-science`,
    },
    name: 'custom',
    destination: 'dist/images/sprite',
  },
};

/**
 * Creates the sources object for the ecl-builder instance.
 *
 * @param {string} query Should match a globbing pattern where the source assets
 * are located.
 * @param {object} options Should contain the supported options for the defined asset compiler.
 * @param {array} replacments Optional query array that should contain the
 * ${string}:${replace} replacement pattern.
 */
const defineBuilderEntries = (query, options, replacements) =>
  sync(query).map((entry) => {
    let dest = path.resolve(config.dest, path.relative(__dirname, entry));

    if (replacements && replacements.length) {
      replacements.forEach((replacment) => {
        const [o, n] = replacment.split(':');
        const regex = new RegExp(o, 'g');

        dest = dest.replace(regex, n);
      });
    }

    return {
      entry,
      dest,
      options,
    };
  });

module.exports = {
  config,
  scripts: defineBuilderEntries(path.join(__dirname, 'js/**/*.js'), script_options, [
    '/js:/scripts',
  ]),
  styles: defineBuilderEntries(path.join(__dirname, 'sass/*.scss'), style_options, [
    '/sass:/styles',
    '.scss:.css',
  ]),
  copy: [
    {
      from: path.resolve(nodeModules, '@ecl/ec-preset-editor/dist'),
      to: config.dest,
    },
    {
      from: path.resolve(nodeModules, '@ecl/ec-preset-legacy-website/dist/images'),
      to: path.resolve(config.dest, 'images'),
    },
    {
      from: path.resolve(nodeModules, '@ecl/ec-preset-legacy-website/dist/scripts'),
      to: path.resolve(config.dest, 'scripts/vendor/ecl'),
    },
    {
      from: path.resolve(nodeModules, 'svg4everybody/dist'),
      to: path.resolve(config.dest, 'scripts/vendor/svg4everybody'),
    },
    {
      from: path.resolve(nodeModules, 'flag-icons/flags/flags-iso/flat'),
      to: path.resolve(config.dest, 'images/flags'),
    },
    {
      from: path.resolve(nodeModules, 'flag-icons/flags'),
      patterns: 'LICENSE.txt',
      to: path.resolve(config.dest, 'images/flags'),
    },
    {
      from: path.resolve(config.src, 'images/fields-of-science'),
      to: path.resolve(config.dest, 'images/fields-of-science'),
    },
    {
      from: path.resolve(config.src, 'images/other'),
      to: path.resolve(config.dest, 'images/other'),
    }
  ],
};
