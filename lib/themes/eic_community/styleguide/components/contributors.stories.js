import docs from './author.docs.mdx';

import contributorsTemplate from '@theme/patterns/compositions/contributors.html.twig';
import contributors from '@theme/data/contributors.data.js';

export const Default = () => contributorsTemplate(authorcontributorsList);

export default {
  title: 'Components / Contributors',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
