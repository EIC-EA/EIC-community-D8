const path = require('path');
const { TwingEnvironment, TwingLoaderFilesystem } = require('twing');

// Defines the absolute path to the common styleguide packages.
const common = path.resolve(process.cwd(), 'node_modules', '@ecl-twig');

// Defines the absolute path to the theme specific packages.
const theme = path.resolve(process.cwd(), 'patterns');

// Use the resolved paths as base path for the Twing Filesystem.
const loader = new TwingLoaderFilesystem([
    common,
    theme
]);

// In storybook we get this returned as an instance of
// TWigLoaderNull, we need to avoid processing this.
if (typeof loader.addPath === 'function') {
  // Use namespace to maintain the exact incldue paths for both Drupal and
  // Storybook.
  loader.addPath(common, 'ecl');
  loader.addPath(common, 'ecl-twig');
  loader.addPath(theme, 'theme');
}

const environment = new TwingEnvironment(loader, { autoescape: false })

module.exports = environment;