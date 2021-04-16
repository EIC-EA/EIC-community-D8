import checkboxTemplate from '@ecl-twig/ec-component-checkbox/ecl-checkbox-group.html.twig';
import searchTemplate from '@theme/patterns/components/form/search-input.html.twig';
import LinkTemplate from '@ecl-twig/ec-component-link/ecl-link.html.twig';
import inputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';
import selectTemplate from '@ecl-twig/ec-component-select/ecl-select.html.twig';

import { checkboxes, select } from '@theme/data/form.data';
import common from '@theme/data/common.data';

export default [
  {
    label: 'Search for a member',
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
            label: 'Invite a member',
          },
          extra_classes: 'ecl-filter-sidebar__link ecl-link--button ecl-link--button-primary',
        }),
      },
    ],
  },
  {
    label: 'Tags',
    items: checkboxes.map((checkbox) => ({
      content: checkboxTemplate(checkbox),
    })),
  },
];
