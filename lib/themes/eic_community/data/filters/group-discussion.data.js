import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import searchTemplate from '@theme/patterns/components/form/search-input.html.twig';
import inputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';
import selectTemplate from '@ecl-twig/ec-component-select/ecl-select.html.twig';

import { checkboxes, select } from '@theme/data/form.data';
import common from '@theme/data/common.data';

export default [
  {
    label: 'Search for a discussion',
    items: [
      {
        content: searchTemplate({
          icon_file_path: common.icon_file_path,
        }),
      },
    ],
  },
  {
    label: 'Type',
    items: checkboxes.slice(0, 3).map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Topics',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Region & Country',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
  {
    label: 'Tags',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
];
