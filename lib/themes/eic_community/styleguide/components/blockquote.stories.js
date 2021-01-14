import docs from './blockquote.docs.mdx';

import blockquoteTemplate from '~/patterns/components/blockquote.html.twig';
import blockquote from '~/data/blockquote.data.js';

export const Base = () => blockquoteTemplate(blockquote);

export default {
  title: 'Components / Blockquote',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
