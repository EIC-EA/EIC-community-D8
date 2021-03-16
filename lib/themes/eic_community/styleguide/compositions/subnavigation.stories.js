import docs from './subnavigation.docs.mdx';

import SubnavigationTemplate from '@theme/patterns/compositions/subnavigation.html.twig';

import subnavigation from '@theme/data/subnavigation';

import { without } from '@theme/snippets';

export const DiscussionBase = () =>
  SubnavigationTemplate(without(subnavigation.discussion, 'stats'));

export const DiscussionStats = () => SubnavigationTemplate(subnavigation.discussion);

export default {
  title: 'Compositions / Subnavigation',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
