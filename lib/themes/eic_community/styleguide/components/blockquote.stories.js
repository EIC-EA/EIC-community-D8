export default {
  title: 'Components / Blockquote',
};

import blockquoteTemplate from '~/patterns/components/blockquote.html.twig';
import blockquote from '~/data/blockquote.data.js';

export const Base = () => blockquoteTemplate(blockquote);
