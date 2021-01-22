import common from './common.data';

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
        type: 'general',
        name: 'share',
      },
    },
  ],
  image: {
    src: 'http://placehold.it/144x144',
    alt: 'Avatar image of Jane Doe',
  },
};
