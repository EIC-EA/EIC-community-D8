import common from '@theme/data/common.data';

export default {
  title: 'Menu',
  close: 'Close',
  back: 'Back',
  icon_path: common.icon_file_path,
  site_name: common.site_name,
  menu_link: '/',
  items: [
    {
      label: 'Stories',
      path: '?path=stories',
    },
    {
      label: 'Groups',
      path: '?path=groups',
    },
    {
      label: 'Events',
      path: '?path=stories',
    },
    {
      label: 'Projects',
      path: 'example',
      is_current: false,
      children: [
        {
          label: 'Item 1.1',
          path: '/example',
        },
      ],
    },
    {
      label: 'Companies',
      path: '?path=companies',
    },
    {
      label: 'People',
      path: '?path=people',
    },
    {
      label: 'Find Investors',
      path: '?path=find-investors',
    },
  ],
  extra_attributes: [
    {
      name: 'data-ecl-menu',
    },
  ],
};
