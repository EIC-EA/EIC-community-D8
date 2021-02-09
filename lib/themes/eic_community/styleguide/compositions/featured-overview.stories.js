import docs from './featured-overview.docs.mdx';

import featuredOverviewTemplate from '@theme/patterns/compositions/featured-overview.html.twig';
import featuredOverview from '@theme/data/featured-overview';

export const Base = () => featuredOverviewTemplate(featuredOverview.card);

export const Compact = () =>
  featuredOverviewTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-overview--is-compact',
      },
      featuredOverview.card
    )
  );

export default {
  title: 'Compositions / Featured Overview',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
