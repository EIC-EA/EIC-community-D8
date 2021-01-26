import docs from './editorial-header.docs.mdx';

import editorialArticleTemplate from '@theme/patterns/compositions/editorial-article.html.twig';
import wysiwyg from '@theme/snippets/wysiwyg-example.html.twig';

export const Base = () =>
  editorialArticleTemplate({
    content: wysiwyg(),
  });

export default {
  title: 'Compositions / Editorial Article',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
