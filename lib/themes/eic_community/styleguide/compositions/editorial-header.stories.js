import docs from './editorial-header.docs.mdx';

import editorialHeaderTemplate from '@theme/patterns/compositions/editorial-header.html.twig';
import editorialHeader from '@theme/data/editorial-header.data.js';

export const Base = () => editorialHeaderTemplate(editorialHeader);

export default {
  title: 'Compositions / Editorial Header',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
