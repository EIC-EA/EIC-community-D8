import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  image: {
    src: 'https://picsum.photos/160/120',
  },
  path: '?teaser=member',
  status: {
    label: 'CEO',
    icon: {
      type: 'custom',
      name: 'trophy_circle'
    }
  },
  title: 'John Delaware',
  job_titles: 'General Manager',
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
      label: 'Last activity',
      value: 'Last activity 3 days ago',
      icon: {
        name: 'time',
        type: 'custom',
      },
    },
  ],
};
