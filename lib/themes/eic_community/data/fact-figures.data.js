import common from '@theme/data/common.data';

export default {
  column: 3,
  display_icons: true,
  icon_file_path: common.icon_file_path,
  items: [
    {
      title: 'Members',
      path: '?facts=members',
      value: 11900,
      description: 'Consectetur ad deserunt consectetur officia velit labore.',
      icon: {
        type: 'custom',
        name: 'user_circle',
      },
    },
    {
      title: 'Companies',
      description:
        'Ut ea culpa sunt adipisicing commodo commodo culpa ullamco reprehenderit deserunt in ex.',
      value: 6306,
      icon: {
        type: 'custom',
        name: 'company',
      },
    },
    {
      title: 'Groups',
      description: 'Ipsum aliqua pariatur proident labore.',
      value: 64,
      icon: {
        type: 'custom',
        name: 'group',
      },
    },
    {
      title: 'Projects',
      description: 'Enim ullamco Lorem deserunt cillum adipisicing aliqua fugiat aute.',
      value: 22,
      icon: {
        type: 'custom',
        name: 'documents',
      },
    },
    {
      title: 'Challenges',
      description: 'Incididunt mollit est deserunt eu laboris pariatur duis eiusmod.',
      value: 16,
      icon: {
        type: 'custom',
        name: 'objectif',
      },
    },
    {
      title: 'Events',
      description:
        'Veniam ad laborum ut id mollit magna fugiat laboris anim cupidatat quis officia nulla.',
      value: 10,
      icon: {
        type: 'custom',
        name: 'calendar',
      },
    },
  ],
};
