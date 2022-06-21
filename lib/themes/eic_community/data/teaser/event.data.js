import common from '@theme/data/common.data';

export default {
  title: 'Cillum et ipsum laborum ea.',
  tags: [
    {
      label: 'Climate',
    },
    {
      label: 'Big Tech',
    },
  ],
  image: {
    src: 'https://picsum.photos/160/160',
  },
  stats: [
    {
      label: 'Comments',
      value: 20,
      icon: {
        type: 'custom',
        name: 'comment',
      },
    },
    {
      label: 'Views',
      value: 32,
      icon: {
        type: 'custom',
        name: 'views',
      },
    },
  ],
  actions: [
    {
      label: 'Sign up',
      path: '?teaser=signup',
    },
  ],
  timestamp: {
    label: '45d 2h 30m left to signup',
  },
  date: {
    day: '24',
    month: 'Jun',
    year: '2020',
    full_month: 'June',
    date_time: '2018-06-24',
  },
  icon_file_path: common.icon_file_path,
  type: [
    {
      extra_classes: "ecl-icon--currentColor",
      icon: {
        name: 'remote',
        type: 'custom',
      },
      label: 'Remote event',
    },
    {
      extra_classes: "ecl-icon--currentColor",
      icon: {
        name: 'map-marker',
        type: 'custom',
      },
      label: 'Antwerp, Belgium',
    }
  ]
};
