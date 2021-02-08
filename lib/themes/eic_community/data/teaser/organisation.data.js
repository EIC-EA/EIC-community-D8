import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  image: {
    src: 'https://picsum.photos/160/120',
  },
  title: 'Boxface Inc.',
  location: {
    label: 'Antwerpen, België',
  },
  size: {
    label: 'Company size',
  },
  tags: [
    {
      label: 'Big Tech',
      path: '?path=big-tech',
    },
    {
      label: 'Transport',
      path: '?path=transport',
    },
  ],
  stats: [
    {
      label: 'Members',
      value: 20,
      icon: {
        name: 'user',
        type: 'custom',
      },
    },
  ],
};
