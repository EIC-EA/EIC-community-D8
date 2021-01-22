import common from './common.data';

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
            type: 'general',
            name: 'share',
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
            type: 'general',
            name: 'share',
          },
        },
      ],
    },
    {
      author: 'Anonymous',
    },
  ],
};
