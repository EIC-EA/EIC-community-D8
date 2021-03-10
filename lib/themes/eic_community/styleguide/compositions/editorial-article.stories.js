import docs from './editorial-header.docs.mdx';

import EditorialArticleTemplate from '@theme/patterns/compositions/editorial-article.html.twig';

import wysiwyg from '@theme/snippets/wysiwyg-example.html.twig';
import { editableField } from '@theme/snippets';
import common from '@theme/data/common.data';

export const Base = () =>
  EditorialArticleTemplate({
    content: wysiwyg({
      icon_file_path: common.icon_file_path,
    }),
  });

export const WithSidebar = () =>
  EditorialArticleTemplate({
    content: wysiwyg({
      icon_file_path: common.icon_file_path,
    }),
    sidebar: editableField('Area for the editorial article sidebar.'),
  });

export default {
  title: 'Compositions / Editorial Article',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
