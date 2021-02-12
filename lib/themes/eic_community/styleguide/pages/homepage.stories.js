import HomepagePrivateTemplate from '@theme/patterns/pages/homepage/homepage.private.html.twig';
import HomepagePublicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import cardOverview from '@theme/data/card-overview.data';
import common from '@theme/data/common.data';
import factFigures from '@theme/data/fact-figures.data';
import featuredContent from '@theme/data/featured-content-grid.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

export const HomepagePublic = () =>
  HomepagePublicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    breadcrumb: breadcrumb,
    common: common,
    hero: {
      insertion: 'Welcome to the EIC Community',
      title: 'Your community to find partners and share knowledge.',
      description: 'Ipsum in culpa sunt commodo quis quis sunt reprehenderit ipsum dolore minim.',
      image: 'https://picsum.photos/1600/450',
      actions: [
        {
          link: {
            label: 'Register',
            path: '?path=register',
            type: 'cta',
          },
        },
        {
          link: {
            label: 'Login',
            path: '?path=login',
            type: 'standalone',
          },
        },
      ],
    },
    usp: {
      title: 'As an EIC beneficiary, access a one-stop-shop',
      description:
        'Exercitation laboris exercitation cillum aliqua commodo reprehenderit voluptate non mollit in eu nisi ipsum adipisicing.',
      items: cardOverview.items,
    },
    featured_content: featuredContent,
    fact_figures: factFigures,
  });

export const HomepagePrivate = () =>
  HomepagePrivateTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    common: common,
    editorial_header: {
      icon_file_path: common.icon_file_path,
      title: 'Welcome to EIC',
      actions: [
        {
          link: {
            label: 'My activity feed',
            path: '?path=my-activity-feed',
          },
          icon: {
            name: 'user',
            type: 'custom',
          },
        },
        {
          link: {
            label: 'Add Content',
          },
          icon: {
            name: 'plus',
            type: 'ui',
          },
          items: [
            {
              link: {
                label: 'New Story',
                path: 'foo',
              },
              icon: {
                type: 'custom',
                name: 'news',
              },
            },
            {
              label: 'New event',
              icon: {
                type: 'custom',
                name: 'calendar',
              },
            },
          ],
        },
      ],
    },
    featured_content: Object.assign(
      {
        extra_classes: 'ecl-featured-content-grid--is-compact',
      },
      featuredContent
    ),
  });

export default {
  title: 'Pages / Homepage',
};
