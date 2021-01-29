import docs from './teaser.docs.mdx';

import storyTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import memberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';

import teaser from '@theme/data/teaser.data';

export const StoryTeaser = () => storyTemplate(teaser.story);

export const MemberTeaser = () => memberTemplate(teaser.member);

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
