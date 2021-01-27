import docs from './story-teaser.docs.mdx';

import blockquoteTemplate from '@theme/patterns/components/blockquote.html.twig';
import blockquote from '@theme/data/blockquote.data.js';

export const Base = () => blockquoteTemplate(blockquote);

export default {
  title: 'Components / Blockquote',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
