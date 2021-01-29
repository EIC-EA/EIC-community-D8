import docs from './editorial-actions.docs.mdx';

import featuredContributorsTemplate from '@theme/patterns/compositions/editorial-actions.html.twig';
import editorialActions from '@theme/data/editorial-actions.data';

export const Base = () => featuredContributorsTemplate(editorialActions);

export default {
  title: 'Compositions / Editorial actions',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
