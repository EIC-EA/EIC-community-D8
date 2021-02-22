import { lorem } from 'faker';

// Output a basis paragraph HTMLElement.
export const editableField = (content) => `
  <div><p>${content || lorem.sentence()}</p></div>
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
