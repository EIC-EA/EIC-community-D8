import docs from './featured-contributors.docs.mdx';

import FeaturedContributorsTemplate from '@theme/patterns/compositions/featured-contributors.html.twig';

import contributors from '@theme/data/contributors.data.js';

export const Default = () => FeaturedContributorsTemplate(contributors);

export default {
  title: 'Compositions / Featured Contributors',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
