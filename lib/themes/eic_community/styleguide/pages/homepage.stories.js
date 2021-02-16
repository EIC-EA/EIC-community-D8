import HomepagePrivateTemplate from '@theme/patterns/pages/homepage/homepage.private.html.twig';
import HomepagePublicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import factFigures from '@theme/data/fact-figures.data';
import featuredContentGrid from '@theme/data/featured-content-grid.data';
import featuredContentCollection from '@theme/data/featured-content-collection';
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
    usp: featuredContentCollection.card,
    featured_events: featuredContentCollection.event,
    featured_news: featuredContentGrid,
    fact_figures: factFigures,
    social_share: {
      title: 'Follow us',
      icon_file_path: common.icon_file_path,
      items: [
        {
          path: '?social-share=twitter',
          name: 'twitter',
          label: 'Twitter',
        },
        {
          path: '?social-share=facebook',
          name: 'facebook-current',
          label: 'Facebook',
          type: 'custom',
        },
        {
          path: '?social-share=linkedin',
          name: 'linkedin',
          label: 'LinkedIn',
        },
      ],
    },
  });

export const HomepagePrivate = () =>
  HomepagePrivateTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    common: common,
    usp: featuredContentCollection.card,
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
    fact_figures: (() => {
      const { items, ...p } = factFigures;

      return {
        ...p,
        items: items.filter((i, index) => index < 4),
      };
    })(),
    featured_events: featuredContentCollection.event,
    featured_news: (() => {
      const { items, ...p } = featuredContentGrid;

      return {
        ...p,
        items: items.filter((i, index) => index < 3),
      };
    })(),
    featured_events: featuredContentCollection.event,
  });

export default {
  title: 'Pages / Homepage',
};
