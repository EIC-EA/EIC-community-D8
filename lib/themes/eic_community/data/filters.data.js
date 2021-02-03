import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import inputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';
import selectTemplate from '@ecl-twig/ec-component-select/ecl-select.html.twig';

import { checkboxes, select } from '@theme/data/form.data';

export const story = [
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
    items: checkboxes.slice(0, 3).map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Categories',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Language',
    items: [
      {
        content: selectTemplate(select),
      },
    ],
  },
];

export const member = [
  {
    label: 'Expertise',
    items: checkboxes.slice(0, 3).map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Categories',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
];

export default {
  member,
  story,
};
