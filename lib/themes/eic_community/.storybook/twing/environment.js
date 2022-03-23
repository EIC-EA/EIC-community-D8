const path = require('path');
const { TwingEnvironment, TwingLoaderFilesystem, TwingFunction } = require('twing');

const dumpFunction = require('./dump.function');

const withoutFilter = require('./without.filter');

const renderFilter = require('./render.filter');

// Defines the absolute path to the common styleguide packages.
const ecl = path.resolve(process.cwd(), 'node_modules', '@ecl-twig');

// Defines the absolute path to the theme specific packages.
const theme = path.resolve(process.cwd());

// Use the resolved paths as base path for the Twing Filesystem.
const loader = new TwingLoaderFilesystem([ecl, theme]);

// In storybook we get this returned as an instance of
// TWigLoaderNull, we need to avoid processing this.
if (typeof loader.addPath === 'function') {
  // Use namespace to maintain the exact incldue paths for both Drupal and
  // Storybook.
  loader.addPath(ecl, 'ecl');
  loader.addPath(ecl, 'ecl-twig');
  loader.addPath(theme, 'theme');
}

const environment = new TwingEnvironment(loader, { autoescape: false });

if (typeof environment.addFunction === 'function') {
  // Implements the missing Drupal related Twig functions for Storybook.
  environment.addFunction(dumpFunction);

  // Implements the missing Drupal related Twig functions for Storybook.
  environment.addFilter(withoutFilter);

  environment.addFilter(renderFilter);
}

module.exports = environment;
