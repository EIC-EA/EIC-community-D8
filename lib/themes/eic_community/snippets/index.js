import { lorem } from 'faker';

/**
 * Renders the basic html that would come from a Drupal field.
 *
 * @param {string} content The contents that will be placed within the field.
 */
export const editableField = (content) => `
  <div><p>${typeof content === 'string' ? content : lorem.sentence(content || 32)}</p></div>
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

/**
 * Slices the defined array or object with the defind length.
 * @param {array|object} data The array or object that will be filtered.
 * @param {number} length Limits the array or object the the defined length
 * @returns
 */
export const slice = (data, length = 3) => {
  if (!data) {
    return;
  }

  if (Array.isArray(data)) {
    return data.slice(length);
  }

  if (data instanceof Object) {
    const output = Object.keys(data).reduce((acc, current, index) => {
      if (index < length) {
        acc[current] = data[current];
      }

      return acc;
    }, {});

    return output;
  }

  return data;
};

function shuffle(array) {
  let currentIndex = array.length,  randomIndex;

  while (currentIndex != 0) {

    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex--;

    [array[currentIndex], array[randomIndex]] = [
      array[randomIndex], array[currentIndex]];
  }

  return array;
}

export const randomString = (o) => {
  const options = {
    string: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    length: Math.ceil(Math.random() * 7),
    ...o
  }
  return shuffle(options.string.split(" ")).slice(0, options.length).join(" ")
}
