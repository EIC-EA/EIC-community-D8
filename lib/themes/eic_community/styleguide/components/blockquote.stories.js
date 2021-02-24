import docs from './blockquote.docs.mdx';

import blockquoteTemplate from '@theme/patterns/components/blockquote.html.twig';
import blockquote from '@theme/data/blockquote.data.js';

import { without } from '@theme/snippets';

export const Base = () => blockquoteTemplate(without(blockquote, 'author', 'image'));

export const Author = () => blockquoteTemplate(without(blockquote, 'image'));

export const AuthorAndImage = () => blockquoteTemplate(blockquote);

export default {
  title: 'Components / Blockquote',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
