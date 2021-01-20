import docs from './featured-contributors.docs.mdx';

import featuredContributorsTemplate from '@theme/patterns/compositions/featured-contributors.html.twig';
import contributors from '@theme/data/contributors.data.js';

export const Default = () => featuredContributorsTemplate(contributors);

export default {
  title: 'Components / Featured Contributors',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
