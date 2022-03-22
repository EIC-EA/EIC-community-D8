const { TwingFilter } = require('twing');

/**
 * Implements the Twing without filter that removes the defined key names from
 * the given input.
 *
 * @param {string[]} args Removes the defined data keys from the given input.
 */
const fn = new TwingFilter('render', (...args) => {
  const [data, ...filter] = args;

  const result = {};

  Object.keys(data).forEach((key) => {
    if (filter.includes(key)) {
      return;
    }

    result[key] = data[key];
  });

  return result;
});

module.exports = fn;
