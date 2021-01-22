const { config } = require('./ecl-builder.config');
const SVGSpriteCompiler = require('./scripts/SVGSpriteCompiler');

const sprites = new SVGSpriteCompiler(config);

module.exports = (async () => {
  await sprites.define(config.sprites, 'dist/images', 'custom');
})();
