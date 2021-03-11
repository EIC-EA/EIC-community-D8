import { mockItems } from '@theme/snippets';

export default {
  author: author,
  title: 'Consequat mollit culpa laborum id occaecat.',
  path: '?path=discussion-thread',
  from_contributor: true,
  description: 'Culpa in quis amet consequat officia magna dolor dolore amet eu in sit magna.',
  icon_file_path: common.icon_file_path,
  members: mockItems(author, 99),
  timestamp: {
    label: 'Just now',
  },
  stats: [
    {
      label: 'Reactions',
      value: 4,
      icon: {
        name: 'feedback',
        type: 'general',
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
