import docs from './teaser-overview.docs.mdx';

import { editableField, mockItems } from '@theme/snippets';
import common from '@theme/data/common.data';
import teaserOverview from '@theme/data/teaser-overview.data';

import teaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';

export const Base = () =>
  teaserOverviewTemplate({
    title: 'Example overview',
    items: mockItems(
      {
        content: editableField(),
      },
      10
    ),
  });

export const Gallery = () =>
  teaserOverviewTemplate({
    title: 'Example gallery',
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
