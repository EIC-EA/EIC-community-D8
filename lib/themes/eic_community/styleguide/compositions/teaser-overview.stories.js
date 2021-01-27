import docs from './teaser-overview.docs.mdx';

import { editableField } from '@theme/snippets';

import teaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';

const items = [];
for (let index = 0; index < 10; index++) {
  items.push({ content: editableField });
}

export const Base = () =>
  teaserOverviewTemplate({
    items: items,
  });

export const Gallery = () =>
  teaserOverviewTemplate({
    extra_classes: 'ecl-teaser-overview--has-columns',
    items: items,
  });

export default {
  title: 'Compositions / Teaser Overview',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
