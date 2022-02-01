import docs from './follow-us.docs.mdx';

import common from '@theme/data/common.data';


import FollowUsTemplate from '@theme/patterns/compositions/follow-us.html.twig';

export const Base = () =>
  FollowUsTemplate({

    title: 'Follow us',
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
        type: 'custom',
      },
      {
        path: '?social-share=linkedin',
        name: 'linkedin',
        label: 'LinkedIn',
      },
    ],

  });

export default {
  title: 'Compositions / Follow',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
