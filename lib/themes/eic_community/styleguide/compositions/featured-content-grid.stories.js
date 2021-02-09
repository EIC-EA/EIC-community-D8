import docs from './featured-content-grid.docs.mdx';

import featuredContentGridTemplate from '@theme/patterns/compositions/featured-content-grid.html.twig';

import featuredContentGrid from '@theme/data/featured-content-grid.data';

import { mockItems } from '@theme/snippets';

export const Base = () => featuredContentGridTemplate(featuredContentGrid);

export const Compact = () =>
  featuredContentGridTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-content-grid--is-compact',
      },
      featuredContentGrid
    )
  );

export default {
  title: 'Compositions / Featured Content Grid',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
