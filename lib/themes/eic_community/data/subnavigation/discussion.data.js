import common from '@theme/data/common.data';

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
  stats: [
    {
      label: 'Members',
      value: 294,
      icon: {
        name: 'group',
        type: 'custom',
      },
      updates: {
        label: 'Latest members from the past 14 days.',
        value: 14,
      },
    },
    {
      label: 'Comments',
      value: 33,
      icon: {
        name: 'feedback',
        type: 'general',
      },
    },
    {
      label: 'Attachments',
      value: 4,
      icon: {
        name: 'documents',
        type: 'custom',
      },
    },
    {
      label: 'events',
      value: 2,
      icon: {
        name: 'calendar',
        type: 'custom',
      },
    },
  ],
};
