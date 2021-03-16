import docs from './author-collection.docs.mdx';

import AuthorCollectionTemplate from '@theme/patterns/components/author-collection.html.twig';

import author from '@theme/data/author.data.js';

import { mockItems } from '@theme/snippets';

export const Single = () =>
  AuthorCollectionTemplate({
    items: mockItems(author, 1),
  });

export const Multiple = () =>
  AuthorCollectionTemplate({
    items: mockItems(author, 10),
  });

export default {
  title: 'Components / Author Collection',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
