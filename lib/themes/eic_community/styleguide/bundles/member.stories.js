export default {
  title: 'Bundles / Member',
};

import teaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';

import teaser from '@theme/data/teaser.data';

export const Teaser = () => teaserTemplate(teaser.member);
