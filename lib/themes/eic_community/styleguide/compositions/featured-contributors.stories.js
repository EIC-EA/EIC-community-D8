import docs from './featured-contributors.docs.mdx';

import FeaturedContributorsTemplate from '@theme/patterns/compositions/featured-contributors.html.twig';

import contributors from '@theme/data/contributors.data.js';

export const Default = () => FeaturedContributorsTemplate(contributors);

export const Collapsible = () =>
  FeaturedContributorsTemplate(
    Object.assign(
      {
        is_collapsible: true,
      },
      contributors
    )
  );
const redirectContributor = {...contributors}
export const Redirect = () =>
  FeaturedContributorsTemplate(
    Object.assign(
      {
        is_collapsible: false,
        call_to_action: {
          link: {
            label: 'View all',
            path: '#'
          },
          icon: {
            size: '2xs'
          }
        }
      },
      {...redirectContributor,...redirectContributor.items = redirectContributor.items.slice(0, 6)}
    )
  );

export const Grid = () =>
  FeaturedContributorsTemplate(
    Object.assign(
      {
        is_collapsible: true,
        grid: true,
      },
      contributors
    )
  );

export default {
  title: 'Compositions / Featured Contributors',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
