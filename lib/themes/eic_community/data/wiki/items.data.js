export default [
  {
    link: {
      label: 'Item #1',
      path: 'http://example.com#1',
    },
    is_active: true,
    updates: {
      label: 'New updates',
      value: 2,
    },
    items: [
      {
        link: {
          label: 'Subitem #1',
          path: 'http://example.com#2',
        },
        items: [
          {
            link: {
              label: 'Subitem #1',
              path: 'http://example.com#2',
            },
          },
          {
            link: {
              label: 'Subitem #2',
              path: 'http://example.com#3',
            },
          },
        ],
      },
      {
        link: {
          label: 'Subitem #2',
          path: 'http://example.com#3',
        },
      },
    ],
  },
  {
    link: {
      label: 'Item #2',
      path: 'http://example.com#4',
    },
  },
  {
    link: {
      label: 'Item #3',
      path: 'http://example.com#5',
    },
    items: [
      {
        link: {
          label: 'Subitem #1',
          path: 'http://example.com#6',
        },
      },
      {
        link: {
          label: 'Subitem #2',
          path: 'http://example.com#7',
        },
      },
    ],
  },
  {
    link: {
      label: 'Item #4',
      path: 'http://example.com#8',
    },
  },
];
