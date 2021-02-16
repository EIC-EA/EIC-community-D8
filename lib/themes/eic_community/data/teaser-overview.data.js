import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  amount_options: {
    title: 'Showing',
    total_label: 'of',
    total: 83,
    items: [
      {
        value: 10,
        label: '10',
      },
      {
        value: 20,
        label: '20',
      },
      {
        value: 50,
        label: '50',
      },
      {
        value: 100,
        label: '100',
      },
    ],
  },
  active_filters: {
    title: 'Active Filters',
    items: [
      {
        label: 'News',
        value: 'content-type=news',
      },
      {
        label: 'Climate',
        value: 'tag=climate',
      },
    ],
  },
  sort_options: {
    title: 'Sort by',
    items: [
      {
        value: 'any',
        label: '- Any -',
      },
      {
        value: 'newest',
        label: 'Newest',
      },
      {
        value: 'oldest',
        label: 'Oldest',
      },
    ],
  },
  interface_options: {
    title: 'View as',
    items: [
      {
        label: 'List',
        icon: {
          type: 'custom',
          name: 'list',
        },
        path: '?view=list',
      },
    ],
  },
};
