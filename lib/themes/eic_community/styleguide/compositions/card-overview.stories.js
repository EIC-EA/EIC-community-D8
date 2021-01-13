export default {
  title: 'Compositions / Card Overview',
};

import cardOverviewTemplate from '~/patterns/compositions/card-overview.html.twig';
import cardOverview from '~/data/card-overview.data.js';

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
