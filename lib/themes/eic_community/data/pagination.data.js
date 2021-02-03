import common from './common.data';

export default {
  label: 'Pagination',
  items: [
    {
      type: 'previous',
      'aria-label': 'Go to previous page',
      link: {
        link: {
          path: '/example#previous',
          label: 'Previous',
          icon_position: 'before',
        },
        icon: {
          path: common.icon_file_path,
          type: 'ui',
          name: 'corner-arrow',
          size: 'xs',
          transform: 'rotate-270',
        },
      },
    },
    {
      'aria-label': 'Go to page 1',
      link: {
        link: {
          path: '/example#page-1',
          label: '1',
        },
      },
    },
    {
      type: 'current',
      'aria-label': 'Page 2',
      label: '2',
    },
    {
      'aria-label': 'Go to page 3',
      link: {
        link: {
          path: '/example#page-3',
          label: '3',
        },
      },
    },
    {
      'aria-label': 'Go to page 4',
      link: {
        link: {
          path: '/example#page-4',
          label: '4',
        },
      },
    },
    {
      type: 'next',
      'aria-label': 'Go to next page',
      link: {
        link: {
          path: '/example#next',
          label: 'Next',
          icon_position: 'after',
        },
        icon: {
          path: common.icon_file_path,
          type: 'ui',
          name: 'corner-arrow',
          size: 'xs',
          transform: 'rotate-90',
        },
      },
    },
  ],
};
