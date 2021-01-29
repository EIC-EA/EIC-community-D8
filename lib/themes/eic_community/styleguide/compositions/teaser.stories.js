import docs from './teaser.docs.mdx';

import teaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import teaser from '@theme/data/teaser.data';

export const StoryTeaser = () => teaserTemplate(teaser.story);

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
