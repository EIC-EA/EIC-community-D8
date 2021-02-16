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
