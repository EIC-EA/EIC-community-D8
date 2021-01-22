const path = require('path');
const imageminSvgo = require('imagemin-svgo');

const pkg = require('./package.json');

const isProd = process.env.NODE_ENV === 'production';

const nodeModules = __dirname + '/node_modules';

// SCSS includePaths
const includePaths = [nodeModules];

const style_options = {
  includePaths,
  sourceMap: isProd ? 'none' : true,
};

const config = {
  src: __dirname,
  dest: path.resolve(__dirname, 'dist'),
  sprites: {
    entry: {
      custom: `./images/custom`,
      branded: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/branded',
      general: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/general',
      notifications:
        './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/notifications',
      ui: './node_modules/@ecl/ec-preset-legacy-website/dist/images/icons/svg/ui',
    },
    name: 'custom',
    destination: 'dist/images',
    plugins: {
      imagemin: {
        use: [
          imageminSvgo({
            plugins: [
              {
                convertPathData: false,
              },
              {
                removeViewBox: false,
              },
              {
                removeAttrs: {
                  attrs: ['(fill|stroke|class|style)', 'svg:(width|height)'],
                },
              },
            ],
          }),
        ],
      },
    },
  },
};

module.exports = {
  config,
  styles: [
    {
      entry: path.resolve(__dirname, 'sass/eic_community.screen.scss'),
      dest: path.resolve(config.dest, 'css/eic_community.screen.css'),
      options: style_options,
    },
    {
      entry: path.resolve(__dirname, 'sass/eic_community.print.scss'),
      dest: path.resolve(config.dest, 'css/eic_community.print.css'),
      options: style_options,
    },
  ],
  copy: [
    { from: path.resolve('./js'), to: path.resolve(config.src, 'dist/scripts') },
    {
      from: path.resolve(nodeModules, '@ecl/ec-preset-editor/dist'),
      to: config.dest,
    },
    {
      from: path.resolve(nodeModules, '@ecl/ec-preset-legacy-website/dist'),
      to: config.dest,
    },
    {
      from: path.resolve(nodeModules, 'svg4everybody/dist'),
      patterns: 'svg4everybody.min.js',
      to: path.resolve(config.dest, 'scripts'),
    },
  ],
};
