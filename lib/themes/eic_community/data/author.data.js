import common from '@theme/data/common.data';

export default {
  name: 'Jane Doe',
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
    src: 'https://picsum.photos/144/144',
    alt: 'Avatar image of Jane Doe',
  },
};
