export default {
  title: 'Bundles / Member',
};

import teaser from '@theme/patterns/compositions/member/member.teaser.html.twig';

import common from '@theme/data/common.data';

export const Teaser = () =>
  teaser({
    title: 'John Delaware',
    image: {
      src: 'http://placehold.it/100x100',
    },
    path: '',
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
  });
