import common from '@theme/data/common.data';

import { mockItems } from '@theme/snippets';

export default {
  title: 'All News & Stories',
  call_to_action: {
    link: {
      label: 'More news & stories',
    },
  },
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      title:
        'In ipsum ullamco labore amet anim in deserunt do excepteur labore incididunt aliquip ipsum do.',
      description:
        'Consectetur mollit aliqua labore laboris commodo laboris voluptate reprehenderit mollit minim cillum non duis.',
      path: '?path=featured-content-grid',
      type: {
        label: 'News',
        icon: {
          name: 'news',
          type: 'custom',
        },
      },
      timestamp: {
        label: '3 hours ago',
      },
      author: {
        name: 'Jane Doe',
        path: '?author=jane-doe',
      },
      media: {
        image: 'https://picsum.photos/640/480',
      },
      stats: [
        {
          value: '32',
          label: 'Comments',
          icon: {
            type: 'custom',
            name: 'comment',
          },
        },
        {
          value: '120',
          label: 'Views',
          icon: {
            type: 'custom',
            name: 'views',
          },
        },
        {
          value: '32',
          label: 'Likes',
          icon: {
            type: 'custom',
            name: 'like',
          },
        },
      ],
    },
    4
  ),
};
