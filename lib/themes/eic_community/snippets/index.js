// Outputs a Drupal field sample for Storybook.
export const editableField = (content) => `
  <div><p>${
    content || 'Est ea irure ex Lorem voluptate anim laborum consectetur et duis irure.'
  }</p></div>
`;

export const mockItems = (payload, amount = 10) => {
  const output = [];

  for (let i = 0; i < amount; i++) {
    output.push(payload);
  }

  return output;
};
