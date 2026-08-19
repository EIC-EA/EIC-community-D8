const { TwingFilter } = require('twing');

/**
 * Implements the Drupal |t (translation) filter for Storybook.
 * In Storybook context we just pass through the string as-is since
 * there's no actual translation backend.
 */
const fn = new TwingFilter('t', (value) => {
  return Promise.resolve(value);
});

module.exports = fn;
