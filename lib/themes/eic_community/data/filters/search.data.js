import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import inputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';
import selectTemplate from '@ecl-twig/ec-component-select/ecl-select.html.twig';

import { checkboxes, select } from '@theme/data/form-elements.data';

export default [
  {
    items: [
      {
        content: checkboxTemplate({
          name: 'checkbox-default',
          items: [
            {
              id: 'checkbox-default--lu',
              value: 'interests-only',
              label: 'My interests only',
            },
          ],
        }),
      },
    ],
  },
  {
    items: [
      {
        content: checkboxTemplate({
          name: 'checkbox-default',
          items: [
            {
              id: 'checkbox-default--lu',
              value: 'groups_content-only',
              label: 'My groups & content only',
            },
          ],
        }),
      },
    ],
  },
  {
    is_collapsed: true,
    label: 'Content types',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    is_collapsed: true,
    label: 'Date',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    is_collapsed: true,
    label: 'Horizontal Topics',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    is_collapsed: true,
    label: 'Themes',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
];
