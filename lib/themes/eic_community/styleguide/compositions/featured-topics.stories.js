import docs from './featured-topics.docs.mdx';

import featuredTopicsTemplate from '@theme/patterns/compositions/featured-topics.html.twig';
import topics from '@theme/data/topics.data.js';

export const Default = () => featuredTopicsTemplate(topics);

export default {
  title: 'Components / Featured Topics',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
