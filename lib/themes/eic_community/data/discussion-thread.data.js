import author from '@theme/data/author.data';
import common from '@theme/data/common.data';

import { mockItems } from '@theme/snippets';

export default {
  author: author,
  title: 'Consequat mollit culpa laborum id occaecat.',
  path: '?path=discussion-thread',
  description: 'Culpa in quis amet consequat officia magna dolor dolore amet eu in sit magna.',
  icon_file_path: common.icon_file_path,
  timestamp: {
    label: 'Just now',
  },
  stats: [
    {
      label: 'Reactions',
      value: 4,
      icon: {
        name: 'comment',
        type: 'custom',
      },
    },
  ],
  tags: [
    {
      label: 'Bio Engineering',
    },
    {
      label: 'Big Data',
    },
  ],
};
