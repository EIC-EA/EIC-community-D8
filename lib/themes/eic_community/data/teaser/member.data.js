import common from '@theme/data/common.data';

export default {
  title: 'John Delaware',
  description: 'Technical Director',
  image: {
    src: 'https://picsum.photos/200',
  },
  path: '?teaser=member',
  type: false,
  job_titles: ['General Manager', 'Operator'],
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
    {
      label: 'View facebook profile',
      path: '?path=facebook',
      icon: {
        type: 'custom',
        name: 'facebook',
      },
    },
    {
      label: 'View Twitter profile',
      path: '?path=facebook',
      icon: {
        type: 'custom',
        name: 'twitter',
      },
    },
    {
      label: 'View LinkedIn page',
      path: '?path=linkedin',
      icon: {
        type: 'custom',
        name: 'linkedin',
      },
    },
  ],
  organisations: [
    {
      label: 'Boxface Inc.',
      path: '?organisation=boxface_inc',
    },
  ],
  location: {
    label: 'Antwerpen, België',
  },
};
