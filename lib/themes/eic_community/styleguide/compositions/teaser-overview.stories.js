import docs from './teaser-overview.docs.mdx';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';

import { editableField, mockItems } from '@theme/snippets';
import common from '@theme/data/common.data';
import teaserOverview from '@theme/data/teaser-overview.data';

export const Base = () =>
  TeaserOverviewTemplate({
    title: 'Example overview',
    items: mockItems(
      {
        content: editableField(),
      },
      10
    ),
  });

export const Gallery = () =>
  TeaserOverviewTemplate({
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
  TeaserOverviewTemplate(
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
