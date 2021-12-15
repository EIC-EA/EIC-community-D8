import common from '@theme/data/common.data';

export default {
  title:
    'Wireframe title',
  path: '?teaser=wiki',
  description:
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus ultricies auctor tempus. Nulla nec lectus nisi. Donec id laoreet lacus.',
  timestamp: {
    label: '16 hours ago',
  },
  icon_file_path: common.icon_file_path,
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
      value: '32',
      label: 'Likes',
      icon: {
        type: 'custom',
        name: 'like',
      },
    },
  ],
};
