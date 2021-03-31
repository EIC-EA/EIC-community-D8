import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import searchTemplate from '@theme/patterns/components/form/search-input.html.twig';
import LinkTemplate from '@ecl-twig/ec-component-link/ecl-link.html.twig';
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
    items: [
      {
        content: LinkTemplate({
          link: {
            label: 'Start a discussion',
          },
          extra_classes: 'ecl-filter-sidebar__link ecl-link--button ecl-link--button-primary',
        }),
      },
    ],
  },
  {
    items: [
      {
        content: '<h2 class="ecl-filter-sidebar-title">Filter</h2>',
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
