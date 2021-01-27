import { fillContentItems } from '@theme/snippets';
import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import inputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';

import { checkboxes } from '@theme/data/form.data';

let baseStory = [
  {
    label: 'Search events',
    items: [
      {
        content: inputTemplate(),
      },
    ],
  },
  {
    label: 'Topics',
    items: [],
  },
  {
    label: 'Categories',
    items: [],
  },
  {
    label: 'Language',
    items: [
      {
        content: 'select',
      },
    ],
  },
];

checkboxes.forEach((checkbox) => {
  baseStory = fillContentItems(baseStory, 'Topics', 'label', checkboxTemplate(checkbox), 1);
  baseStory = fillContentItems(baseStory, 'Categories', 'label', checkboxTemplate(checkbox), 3);
});

export const story = baseStory;

export default {
  story,
};
