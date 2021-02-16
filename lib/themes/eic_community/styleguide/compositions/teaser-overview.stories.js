import docs from './teaser-overview.docs.mdx';

import { editableField, mockItems } from '@theme/snippets';
import common from '@theme/data/common.data';
import teaserOverview from '@theme/data/teaser-overview.data';

import teaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';

const items = [];
for (let index = 0; index < 10; index++) {
  items.push({ content: editableField });
}

export const Base = () =>
  teaserOverviewTemplate({
    items: mockItems(
      {
        content: editableField(),
      },
      10
    ),
  });

export const Gallery = () =>
  teaserOverviewTemplate({
    extra_classes: 'ecl-teaser-overview--has-columns',
    items: mockItems(
      {
        content: editableField(),
      },
      10
    ),
  });

export const WithFilters = () =>
  teaserOverviewTemplate(
    Object.assign(
      {
        items: mockItems({
          content: editableField(),
        }),
      },
      teaserOverview
    )
  );

export default {
  title: 'Compositions / Teaser Overview',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
