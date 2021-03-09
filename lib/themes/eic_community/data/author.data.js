import common from '@theme/data/common.data';

export default {
  author: 'Jane Doe',
  description: 'Irure adipisicing labore enim cillum mollit cupidatat velit reprehenderit fugiat',
  path: '?author=amFuZWRvZQ==',
  icon_file_path: common.icon_file_path,
  actions: [
    {
      label: 'Mail',
      path: 'mailto:info@example.com',
      icon: {
        type: 'custom',
        name: 'mail',
      },
    },
  ],
  image: {
    src: 'http://placehold.it/144x144',
    alt: 'Avatar image of Jane Doe',
  },
};
