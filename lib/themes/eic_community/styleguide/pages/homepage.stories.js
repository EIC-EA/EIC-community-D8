import homepagePrivateTemplate from '@theme/patterns/compositions/homepage/homepage.private.html.twig';
import homepagePublicTemplate from '@theme/patterns/compositions/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';
import siteFooter from '@theme/data/site-footer.data.js';
import siteHeader from '@theme/data/site-header.data.js';

export const homepagePublic = () =>
  homepagePublicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
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
  });

export const HomepagePrivate = () =>
  homepagePrivateTemplate({
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
  });

export default {
  title: 'Pages / Homepage',
};
