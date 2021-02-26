export default {
  icon_file_path: 'images/sprite/custom/sprites/custom.svg',
  user: {
    label: 'John Doe',
    path: '?path=user-john-doe',
    image: {
      src: 'https://picsum.photos/64',
    },
    updates: 2,
    actions: [
      {
        link: {
          path: '?path=activity',
          label: 'My activity',
        },
      },
      {
        link: {
          path: '?path=activity',
          label: 'Profile',
        },
      },
      {
        link: {
          path: '?path=logout',
          label: 'Logout',
        },
      },
    ],
  },
};
