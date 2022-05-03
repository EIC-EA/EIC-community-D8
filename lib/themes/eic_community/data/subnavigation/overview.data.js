import common from '@theme/data/common.data';
import searchform from '@theme/data/form-elements.data';

export default {
  icon_file_path: common.icon_file_path,
  items: [
    {
      link: {
        label: 'Overview',
      },
      is_active: true,
    },
    {
      link: {
        label: 'Latest Activity',
      },
    },
    {
      link: {
        label: 'Discussions',
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
