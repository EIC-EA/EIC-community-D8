import docs from './teaser.docs.mdx';

import teaserStory from '@theme/patterns/compositions/story/story.teaser.html.twig';

export const StoryTeaser = () =>
  teaserStory({
    title:
      'Sunt voluptate ea proident incididunt dolore minim tempor ullamco officia nisi magna in amet.',
  });

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
