import common from '@theme/data/common.data';

export default {
  title: 'John Delaware',
  image: {
    src: 'https://picsum.photos/200',
  },
  path: '?teaser=member',
  type: {
    label: 'Top contributor',
    icon: {
      name: 'star_circle',
      type: 'custom',
    },
  },
  job_titles: ['General Manager', 'Operator'],
  locations: ['Antwerpen, Belgium'],
  icon_file_path: common.icon_file_path,
  actions: [
    {
      label: 'Contact by E-mail',
      path: 'mailto:?john_delaware@example.com',
      icon: {
        type: 'custom',
        name: 'mail',
      },
    },
  ],
  timestamp: {
    label: 'Last active 3 days ago',
  },
  organisations: [
    {
      label: 'Boxface Inc.',
      path: '?organisation=boxface_inc',
    },
  ],
  location: {
    label: 'Antwerpen, België',
  },
  stats: [
    {
      label: 'Total comments',
      value: '12',
      icon: {
        type: 'general',
        name: 'feedback',
      },
    },
  ],
};
