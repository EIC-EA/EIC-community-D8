const { statSync, writeFileSync } = require('fs');
const { sync } = require('glob');
const imagemin = require('imagemin');
const imageminSvgo = require('imagemin-svgo');
const mkdirp = require('mkdirp');
const { basename, dirname, join, resolve } = require('path');
const svgstore = require('svgstore');
const { yellow, green } = require('chalk');

const { config } = require('../ecl-builder.config');

/**
 * Creates a single icon sprite by combining SVG files from multiple sources.
 */
return new Promise(async (cb) => {
  const sources = {};
  const map = [];
  const { destination, entry, plugins, name } = config.sprites;

  // Defines all sources from the entry object.
  Object.keys(entry).forEach((type) => {
    if (!sources[type]) {
      sources[type] = [];
    }

    const glob = sync(`${entry[type]}/**.svg`).filter((file) => statSync(file).size);

    if (!glob.length) {
      console.log(yellow(`[build:sprites] > Unable to build ${type} sprites.`));
    }

    sources[type] = glob;

    glob.forEach((source) => map.push(source));
  });

  // Ensures the sprite entries can be used within an inline SVG sprite.
  const stream = await imagemin(map, {
    plugins: [
      imageminSvgo({
        plugins: [
          {
            removeUselessStrokeAndFill: {
              fill: false,
              stroke: false
            }
          },
          {
            convertPathData: false,
          },
          {
            removeViewBox: false,
          },
          {
            convertColors: {
              currentColor: true,
            },
          },
          {
            removeAttrs: {
              preserveCurrentColor: true,
              attrs: ['(class|style)', 'svg:(width|height)'],
            },
          },
          {
            cleanupNumericValues: {
              floatPrecision: 7,
            },
          }
        ],
      }),
    ],
  });

  if (!stream) {
    cb();
  }

  // Write each entry as a separate svg image.
  stream.forEach((blob) => {
    const type = Object.keys(entry).filter((c) => sources[c].includes(blob.sourcePath))[0];
    const cwd = join(destination, name, 'svg', type);
    const entryDestination = join(cwd, basename(blob.sourcePath));

    mkdirp.sync(cwd);

    writeFileSync(entryDestination, blob.data);
  });

  // Create the actual sprite
  const sprite = stream.reduce(
    (store, blob, index) => {
      const type = Object.keys(entry).filter((c) => sources[c].includes(blob.sourcePath))[0];

      return store.add(`${type}--` + basename(blob.sourcePath, '.svg'), blob.data);
    },
    svgstore({
      inline: true,
      svgAttrs: {
        xmlns: 'http://www.w3.org/2000/svg',
      },
    })
  );

  if (!sprite) {
    cb();
  }

  const spriteDestination = join(destination, name, 'sprites', `${name}.svg`);

  mkdirp.sync(dirname(spriteDestination));

  writeFileSync(resolve(spriteDestination), sprite.toString());

  console.log(green(`[build:sprites] > Custom svg sprite created: ${spriteDestination}`));

  cb();
});
