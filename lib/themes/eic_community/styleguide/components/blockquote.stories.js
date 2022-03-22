import docs from './blockquote.docs.mdx';

import BlockquoteTemplate from '@theme/patterns/components/blockquote.html.twig';

import blockquote from '@theme/data/blockquote.data.js';

import { without } from '@theme/snippets';

export const Base = () => BlockquoteTemplate(without(blockquote, 'author', 'image'));

export const Author = () => BlockquoteTemplate({...without(blockquote, 'image'), author: true});

export const AuthorAndImage = () => BlockquoteTemplate({...blockquote, author: true});

export default {
  title: 'Components / Blockquote',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
