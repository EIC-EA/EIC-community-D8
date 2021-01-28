import docs from './featured-stories.docs.mdx';

import featuredStoriesTemplate from '@theme/patterns/compositions/featured-stories.html.twig';
import stories from '@theme/data/stories.data.js';

export const Default = () => featuredStoriesTemplate(stories);

export default {
  title: 'Components / Featured Stories',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
