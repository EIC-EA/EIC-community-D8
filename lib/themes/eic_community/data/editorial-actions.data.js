import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  items: [
    {
      extra_classes: 'ecl-link--guest',
      link: {
        label: 'Recommend (27)',
        path: '?path=recommend',
      },
      icon: {
        type: 'custom',
        name: 'like',
      },
    },
    {
      extra_classes: 'ecl-link--guest',
      link: {
        label: 'Bookmark',
        path: '?path=bookmark',
      },
      icon: {
        type: 'custom',
        name: 'star_circle',
      },
    },
  ],
};
