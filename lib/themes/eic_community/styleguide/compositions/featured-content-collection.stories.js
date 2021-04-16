import docs from './featured-content-collection.docs.mdx';

import FeaturedContentCollectionTemplate from '@theme/patterns/compositions/featured-content-collection.html.twig';

import featuredContentCollection from '@theme/data/featured-content-collection';

export const Cards = () => FeaturedContentCollectionTemplate(featuredContentCollection.card);

export const Events = () =>
  FeaturedContentCollectionTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-content-collection--has-compact-layout',
      },
      featuredContentCollection.event
    )
  );

export const Collapsible = () =>
  FeaturedContentCollectionTemplate(
    Object.assign(
      {
        is_collapsible: true,
        extra_classes: 'ecl-featured-content-collection--has-overview-layout',
      },
      featuredContentCollection.event
    )
  );

export default {
  title: 'Compositions / Featured Content Collection',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
