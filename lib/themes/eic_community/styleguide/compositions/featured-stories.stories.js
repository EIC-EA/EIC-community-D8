import docs from './featured-stories.docs.mdx';

import FeaturedStoriesTemplate from '@theme/patterns/compositions/featured-stories.html.twig';

import stories from '@theme/data/stories.data.js';

export const Default = () => FeaturedStoriesTemplate(stories);

export default {
  title: 'Components / Featured Stories',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
