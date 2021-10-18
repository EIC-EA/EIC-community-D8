import docs from './follow-us.docs.mdx';
import common from '@theme/data/common.data';


import FollowUsTemplate from '@theme/patterns/components/follow-us.html.twig';

export const twitter = () =>
  FollowUsTemplate({
    icon_file_path: common.icon_file_path,
    path: '?social-share=twitter',
    name: 'twitter',
    label: 'Twitter',
  });

export const facebook = () =>
  FollowUsTemplate({
    icon_file_path: common.icon_file_path,
    path: '?social-share=facebook',
    name: 'facebook',
    label: 'Facebook',
    type: 'custom',
  });

export const linkedin = () =>
  FollowUsTemplate({
    icon_file_path: common.icon_file_path,
    path: '?social-share=linkedin',
    name: 'linkedin',
    label: 'LinkedIn',
  });

export default {
  title: 'Components / Follow',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
