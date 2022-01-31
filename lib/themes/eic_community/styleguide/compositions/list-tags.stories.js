import docs from "./featured-contributors.docs.mdx";
import ListTagsTemplate from '@theme/patterns/compositions/list-tags.html.twig';

import ListTagsData from '@theme/data/list-tags.data.js';

export const Collapsible = () => ListTagsTemplate(ListTagsData);

export const CollapsibleOneItem = () => ListTagsTemplate({
  ...ListTagsData,
  items: [
    {
      tag: {
        type: "link",
        path: "/component-library/example",
        label: "Link tag"
      }
    }
  ]
});

export default {
  title: 'Compositions / Tags list',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
