import common from './common.data';

export default {
  title: 'Share',
  icon_file_path: common.icon_file_path,
  items: [
    {
      path: '?social-share=twitter',
      name: 'twitter',
      label: 'Twitter',
    },
    {
      path: '?social-share=facebook',
      name: 'facebook-current',
      label: 'Facebook',
      type: 'custom',
    },
    {
      path: '?social-share=linkedin',
      name: 'linkedin',
      label: 'LinkedIn',
    },
    {
      path: 'mailto:info@example.com',
      name: 'mail',
      label: 'Email',
      type: 'custom',
    },
    {
      path: '#',
      name: 'ellipsis',
      label: 'More',
      type: 'custom',
    },
  ],
};
