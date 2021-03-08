import common from '@theme/data/common.data';

export default {
  title: 'Contributors',
  icon_file_path: common.icon_file_path,
  items: [
    {
      desxcription: 'Company XYZ',
      author: 'John Doe',
      path: '?path=am9obmRvZQ==',
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
    },
    {
      description: 'Brand X',
      author: 'Jane Doe',
      path: '?path=amFuZWRvZQ==',
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
    },
    {
      author: 'Anonymous',
    },
  ],
};
