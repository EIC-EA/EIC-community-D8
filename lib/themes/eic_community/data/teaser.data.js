import common from '@theme/data/common.data';

export const story = {
  title:
    'Sunt voluptate ea proident incididunt dolore minim tempor ullamco officia nisi magna in amet.',
  path: '?teaser=member',
  description:
    'Sunt ut laborum fugiat sunt magna sint dolor ullamco laborum cupidatat eu aliqua Lorem.',
  image: {
    src: 'https://picsum.photos/160/120',
  },
  timestamp: {
    label: '12 april 2010',
  },
  type: 'Story',
  icon_file_path: common.icon_file_path,
  icon: {
    type: 'custom',
    name: 'news',
  },
  author: {
    label: 'Urbanus Vliegwiel',
  },
  stats: [
    {
      value: '32',
      label: 'Comments',
      icon: {
        type: 'general',
        name: 'feedback',
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
};

export const member = {
  title: 'John Delaware',
  image: {
    src: 'https://picsum.photos/200',
  },
  path: '?teaser=member',
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

export default {
  story,
  member,
};
