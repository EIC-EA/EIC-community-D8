import docs from './featured-content-collection.docs.mdx';

import FeaturedContentCollectionTemplate from '@theme/patterns/compositions/featured-content-collection.html.twig';
import FeaturedContentCollection from '@theme/data/featured-content-collection';

export const Cards = () => FeaturedContentCollectionTemplate(FeaturedContentCollection.card);

export const Groups = () =>
  FeaturedContentCollectionTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-content-collection--is-compact',
      },
      FeaturedContentCollection.group
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
