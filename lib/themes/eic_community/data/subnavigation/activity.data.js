import common from '@theme/data/common.data';
import searchform from '@theme/data/searchform.data';

export default {
  icon_file_path: common.icon_file_path,
  items: [
    {
      link: {
        label: 'Overview',
      },
    },
    {
      link: {
        label: 'Latest Activity',
      },
      is_active: true,
    },
    {
      link: {
        label: 'Disussions',
      },
    },
    {
      link: {
        label: 'Library',
      },
    },
    {
      link: {
        label: 'Events',
      },
    },
    {
      link: {
        label: 'Wiki',
      },
    },
    {
      link: {
        label: 'About',
      },
    },
    {
      link: {
        label: 'Members',
      },
    },
  ],
  searchform: searchform,
};
