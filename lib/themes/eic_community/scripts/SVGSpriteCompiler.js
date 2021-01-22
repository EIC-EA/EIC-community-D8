const { statSync, writeFileSync } = require('fs');
const { sync } = require('glob');
const imagemin = require('imagemin');
const mkdirp = require('mkdirp');
const { basename, dirname, join, resolve } = require('path');
const svgstore = require('svgstore');

class SVGSpriteCompiler {
  constructor(config) {
    this.config = config;
  }

  /**
   * Defines a new SVG sprite collection from one or multiple sources.
   *
   * @param {object} collection Contains an object where each key should match
   * with the sprite type and the value should match the base directory of the
   * SVG sources.
   */
  define(collection, destination, id) {
    return new Promise(async (cb) => {
      const sources = {};
      const map = [];
      const name = id || 'sprite';

      // Defines all sources from the collection object.
      Object.keys(collection).forEach((type) => {
        if (!sources[type]) {
          sources[type] = [];
        }

        const glob = sync(`${collection[type]}/**.svg`).filter((file) => statSync(file).size);

        sources[type] = glob;

        glob.forEach((source) => map.push(source));
      });

      const { plugins } = this.config.sprites;

      // Ensures the sprite entries can be used within an inline SVG sprite.
      const stream = await imagemin(map, plugins.imagemin);

      if (!stream) {
        cb();
      }

      // Write each entry as a separate svg image.
      stream.forEach((blob) => {
        const type = Object.keys(collection).filter((c) => sources[c].includes(blob.sourcePath))[0];
        const cwd = join(destination, name, 'svg', type);
        const entryDestination = join(cwd, basename(blob.sourcePath));

        mkdirp.sync(cwd);

        writeFileSync(entryDestination, blob.data);
      });

      // Create the actual sprite
      const sprite = stream.reduce(
        (store, blob, index) => {
          const type = Object.keys(collection).filter((c) =>
            sources[c].includes(blob.sourcePath)
          )[0];

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

      console.log(`Custom svg sprite created: ${spriteDestination}`);

      cb();
    });
  }
}

module.exports = SVGSpriteCompiler;
