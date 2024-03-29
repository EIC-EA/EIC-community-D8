export default {
  icon_file_path: 'images/sprite/custom/sprites/custom.svg',
  login: {
    label: 'Login',
  },
  login_title: 'Please log in to see comments and contribute',
  register: {
    label: 'Register',
  },
  user: {
    name: 'John Doe',
    path: '?path=user-john-doe',
    image: {
      src: 'https://picsum.photos/64',
    },
    updates: {
      label: 'New messages',
      value: 2,
    },
    actions: [
      {
        link: {
          path: '?path=feed',
          label: 'My feed',
        },
        active: true,
      },
      {
        link: {
          path: '?path=profile',
          label: 'My profile',
        },
      },
      {
        link: {
          path: '?path=settings',
          label: 'Settings',
        },
      },
      {
        link: {
          path: '?path=drafts',
          label: 'My drafts',
        },
      },
      {
        link: {
          path: '?path=logout',
          label: 'Log out',
        },
      },
    ],
  },
};