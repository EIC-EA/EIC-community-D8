// Outputs a Drupal field sample for Storybook.
export const editableField = (content) => `
  <div><p>${
    content || 'Est ea irure ex Lorem voluptate anim laborum consectetur et duis irure.'
  }</p></div>
`;

export const mockItems = (payload, amount) => {
  const output = [];

  for (let i = 0; i < (amount | 1); i++) {
    output.push(payload);
  }

  return output;
};
