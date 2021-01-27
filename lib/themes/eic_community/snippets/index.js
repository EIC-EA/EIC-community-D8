// Outputs a Drupal field sample for Storybook.
export const editableField = (content) => `
  <div><p>${
    content || 'Est ea irure ex Lorem voluptate anim laborum consectetur et duis irure.'
  }</p></div>
`;

// Helper function to insert dummy data within the field object structure.
export const fillContentItems = (obj, query, key, data, amount = 10) => {
  const output = obj;

  const name = obj
    .map((section, index) => (section[key] && section[key] === query ? index : false))
    .filter((ii) => ii)[0];

  if (output[name] && output[name].items) {
    for (let i = 0; i < amount; i++) {
      output[name].items.push({
        content: data,
      });
    }
  }

  return output;
};
