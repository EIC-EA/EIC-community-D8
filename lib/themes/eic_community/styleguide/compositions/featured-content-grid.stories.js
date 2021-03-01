import docs from './featured-content-grid.docs.mdx';

import FeaturedContentGridTemplate from '@theme/patterns/compositions/featured-content-grid.html.twig';

import featuredContentGrid from '@theme/data/featured-content-grid.data';

export const Base = () => FeaturedContentGridTemplate(featuredContentGrid);

export const Compact = () =>
  FeaturedContentGridTemplate(
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
