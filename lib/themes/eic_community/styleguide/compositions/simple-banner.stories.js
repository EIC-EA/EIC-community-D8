import docs from './simple-banner.docs.mdx';

import SimpleBannerTemplate from '@theme/patterns/compositions/simple-banner.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  SimpleBannerTemplate({
    title: 'Get the conversation started',
    description: '<p>Start discussions with other members.</p>',
    icon_file_path: common.icon_file_path,
    actions: [
      {
        label: 'Help me',
      },
      {
        link: {
          label: 'Logout',
        },
      },
      {
        label: 'Post Content',
        items: [
          {
            link: {
              label: 'New Story',
            },
          },
          {
            link: {
              label: 'New Wiki',
            },
          },
        ],
      },
    ],
  });
export default {
  title: 'Compositions / Simple Banner',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
