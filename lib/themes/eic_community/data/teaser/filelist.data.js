import common from '@theme/data/common.data';

export default {
  title: 'Filelist test lorem ipsum dolor',
  icon_file_path: common.icon_file_path,
  image: {
    src: 'https://picsum.photos/320',
  },
  files: [
    'file1.jpg',
    'file2.doc',
    'file3.png'
  ],
  type: {
    label: 'File'
  },
  language: 'English',
  tags: [
    {
      label: 'Big Tech',
    },
    {
      label: 'Transport',
    },
  ],
  timestamp: {
    label: '3 hours ago',
  },
  author: {
    prefix: 'Uploaded by',
    name: 'John Doe',
    path: '?author=johndoe',
  },
  like: {
      label: 'Like',
      path: '#'
  },
  stats: [
    {
      label: 'Likes',
      value: 287,
      icon: {
        type: 'custom',
        name: 'like',
      },
    },
    {
      label: 'Downloads',
      value: 8,
      icon: {
        type: 'custom',
        name: 'download',
      },
    },
    {
      value: 120,
      label: 'Views',
      icon: {
        type: 'custom',
        name: 'views',
      },
    },

  ],
}
