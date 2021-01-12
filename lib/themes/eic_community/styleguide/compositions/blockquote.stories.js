export default {
  title: 'Compositions / Blockquote',
};

import blockquoteTemplate from '~/patterns/compositions/blockquote.html.twig';
import blockquote from '~/data/blockquote.data.js';

export const Base = () => blockquoteTemplate(blockquote);
