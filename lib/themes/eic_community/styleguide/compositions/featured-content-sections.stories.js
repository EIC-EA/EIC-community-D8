import docs from './featured-content-sections.docs.mdx';

import FeaturedContentSectionsTemplate from '@theme/patterns/compositions/featured-content-sections.html.twig';

import featuredContentSections from '@theme/data/featured-content-sections.data';

export const Base = () => FeaturedContentSectionsTemplate(featuredContentSections);

export const AsTabs = () =>
  FeaturedContentSectionsTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-content-sections--as-tabs',
      },
      featuredContentSections
    )
  );

export default {
  title: 'Compositions / Featured Content Sections',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
