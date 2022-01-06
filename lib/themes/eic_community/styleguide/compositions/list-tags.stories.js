import docs from "./featured-contributors.docs.mdx";
import ListTagsTemplate from '@theme/patterns/compositions/list-tags.html.twig';

import ListTagsData from '@theme/data/list-tags.data.js';

export const Collapsible = () => ListTagsTemplate(ListTagsData);

export default {
  title: 'Compositions / Tags list',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
