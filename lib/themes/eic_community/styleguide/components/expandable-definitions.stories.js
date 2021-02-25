import docs from './expandable-definitions.docs.mdx';

import ExpandableDefinitionsTemplate from '@theme/patterns/components/expandable-definitions.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  ExpandableDefinitionsTemplate({
    icon_file_path: common.icon_file_path,
    items: [
      {
        label: 'Contact information',
        items: [
          {
            label: 'info@example.com',
          },
        ],
      },
      {
        label: 'Locations',
        items: [
          {
            label: 'France',
            path: '?path=france',
          },
          {
            label: 'Spain',
            path: '?path=spain',
          },
        ],
      },
    ],
  });

export default {
  title: 'Components / Expandable Definitions',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
