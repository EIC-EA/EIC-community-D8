import common from '../common.data';

export default {
  title: 'Climate Rangers & Captains',
  icon_file_path: common.icon_file_path,
  type: {
    label: 'Public',
  },
  image: {
    src: 'https://picsum.photos/320',
  },
  owner: {
    label: 'John Doe',
    path: '?owner=john-doe',
    image: {
      src: 'https://picsum.photos/160',
    },
  },
  timestamp: {
    label: 'Last activity 3 hours ago',
  },
  stats: [
    {
      label: 'Members',
      value: 32,
      icon: {
        type: 'custom',
        name: 'user',
      },
    },
    {
      label: 'Reactions',
      value: 287,
      icon: {
        type: 'general',
        name: 'feedback',
      },
    },
    {
      label: 'Documents',
      value: 8,
      icon: {
        type: 'custom',
        name: 'documents',
      },
    },
  ],
};
