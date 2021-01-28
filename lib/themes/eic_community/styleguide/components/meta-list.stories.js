import docs from './meta-list.docs.mdx';

import metaListTemplate from '@theme/patterns/components/meta-list.html.twig';
import metaList from '@theme/data/meta-list.data.js';

export const Base = () => metaListTemplate(metaList);

export default {
  title: 'Components / Meta List',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
