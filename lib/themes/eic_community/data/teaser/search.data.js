import common from '@theme/data/common.data';
import author from '@theme/data/author.data';

export default {
  title:
    'Sunt voluptate ea proident incididunt dolore minim tempor ullamco officia nisi magna in amet.',
  path: '?teaser=member',
  description:
    'Sunt ut laborum fugiat sunt magna sint dolor ullamco laborum cupidatat eu aliqua Lorem.',
  timestamp: {
    label: '12 april 2010',
  },
  icon_file_path: common.icon_file_path,
  details: [
    {
      icon: {
        name: 'wiki',
        type: 'custom',
      },
      contributor: author,
      description: 'created a new <span class="ecl-teaser__detail-type">wiki page</span>',
      timestamp: {
        label: '9 May 2021',
      },
    },
  ],
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
