import { lorem } from 'faker';

/**
 * Renders the basic html that would come from a Drupal field.
 *
 * @param {string} content The contents that will be placed within the field.
 */
export const editableField = (content) => `
  <div><p>${content || lorem.sentence()}</p></div>
`;

/**
 * Implements a basic embed iframe example.
 *
 * @param {string} src The iframe [src] attribute.
 * @param {title} title The iframe [title] attribute.
 */
export const embedField = (src, title = '') => `
  <iframe title="${title}" width="350" height="197" src="${src}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
`;

/**
 * Renders the basic HTML for an editable list.
 *
 * @param {number} amount The amount of list items to insert.
 * @param {string} tag The defined HTMLElement that will be wrapped around the
 * displayed items.
 */
export const editableList = (amount = 5, tag = 'ol') => {
  const children = [];

  for (let i = 0; i < amount; i++) {
    children.push(`<li>${lorem.sentence()}</li>`);
  }

  return `<${tag}>${children.join('')}</${tag}>`;
};

// Output a basic definition list HTMLElement.
export const editableDefinitions = (amount = 5) => {
  const children = [];

  for (let i = 0; i < amount; i++) {
    children.push(`<dt>${lorem.word()}</dt>`);
    children.push(`<dd>${lorem.sentence()}</dd>`);
  }

  return `<dl>${children.join('')}</dl>`;
};

/**
 * Duplicates the given payload into an array until it has as many items as the
 * defined amount.
 *
 * @param {any} payload The data that will be duplicated.
 * @param {number} amount Duplicates the defined data times the amount.
 * @param {function} assigner Optional callback function that overrides the
 * defined payload if the current iteration matches with one of the assignees
 * index values.
 * @param {number[]} assignees Adjusts the payload for the defined index values.
 */
export const mockItems = (payload, amount = 10, assigner, assignees = []) => {
  const output = [];

  for (let i = 0; i < amount; i++) {
    output.push(payload);

    // Inserts additional properties within the selected item index.
    if (Array.isArray(assignees) && typeof assigner === 'function' && assignees.includes(i)) {
      output[i] = assigner();
    }
  }

  return output;
};

/**
 * Removes the defined keys from the given object paramater.
 *
 * @param {Object} object The object that will be filtered.
 * @param  {...any} keys Removes the defined key and it's value from the given object.
 * @returns
 */
export const without = (object, ...keys) => {
  const output = {};

  if (!object instanceof Object) {
    return output;
  }

  Object.keys(object).forEach((name) => {
    if (!keys.includes(name)) {
      output[name] = object[name];
    }
  });

  return output && Object.keys(output).length ? output : object;
};
