import common from '@theme/data/common.data';

export default {
  title: 'Climate Rangers & Captains',
  icon_file_path: common.icon_file_path,
  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
  type: {
    label: 'Public',
    extra_classes: 'ecl-tag--is-public',
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
        name: 'group',
      },
    },
    {
      label: 'Reactions',
      value: 287,
      icon: {
        type: 'custom',
        name: 'comment',
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
