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
      name: 'facebook',
      label: 'Facebook',
    },
    {
      path: '?social-share=linkedin',
      name: 'linkedin',
      label: 'LinkedIn',
    },
  ],
};
