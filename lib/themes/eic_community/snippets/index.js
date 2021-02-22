// Outputs a Drupal field sample for Storybook.
export const editableField = (content) => `
  <div><p>${
    content || 'Est ea irure ex Lorem voluptate anim laborum consectetur et duis irure.'
  }</p></div>
`;

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
