import docs from './featured-topics.docs.mdx';

import FeaturedTopicsTemplate from '@theme/patterns/compositions/featured-topics.html.twig';

import topics from '@theme/data/topics.data.js';

export const Default = () => FeaturedTopicsTemplate(topics);

export default {
  title: 'Components / Featured Topics',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
