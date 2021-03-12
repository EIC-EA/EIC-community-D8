import { lorem } from 'faker';

// Output a basis paragraph HTMLElement.
export const editableField = (content) => `
  <div><p>${content || lorem.sentence()}</p></div>
`;

export const embedField = (src, title = '') => `
  <iframe title="${title}" width="350" height="197" src="${src}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
`;

// Output a basis list HTMLElement.
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

// Duplicates the defined payload within an array by the defined amount.
export const mockItems = (payload, amount = 10) => {
  const output = [];

  for (let i = 0; i < amount; i++) {
    output.push(payload);
  }

  return output;
};

// Filters the given object.
export const without = (object, ...keys) => {
  const output = {};

  Object.keys(object).forEach((name) => {
    if (!keys.includes(name)) {
      output[name] = object[name];
    }
  });

  return output && Object.keys(output).length ? output : object;
};
