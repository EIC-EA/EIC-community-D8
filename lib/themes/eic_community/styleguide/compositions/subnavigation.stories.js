import docs from './subnavigation.docs.mdx';

import SubnavigationTemplate from '@theme/patterns/compositions/subnavigation.html.twig';

import subnavigation from '@theme/data/subnavigation';
import searchform from '@theme/data/form-elements.data';

import { without } from '@theme/snippets';

export const DiscussionBase = () => SubnavigationTemplate(subnavigation.discussion);

export const DiscussionSearchform = () =>
  SubnavigationTemplate(
    Object.assign(
      {
        searchform: searchform,
      },
      subnavigation.discussion
    )
  );

export default {
  title: 'Compositions / Subnavigation',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
