import docs from './card-overview.docs.mdx';

import cardOverviewTemplate from '@theme/patterns/compositions/card-overview.html.twig';
import cardOverview from '@theme/data/card-overview.data.js';

export const Base = () => cardOverviewTemplate(cardOverview);

export const Compact = () =>
  cardOverviewTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-card-overview--is-compact',
      },
      cardOverview
    )
  );

export default {
  title: 'Compositions / Card Overview',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
