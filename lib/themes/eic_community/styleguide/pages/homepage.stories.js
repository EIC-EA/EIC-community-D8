import homepagePrivateTemplate from '@theme/patterns/compositions/homepage/homepage.private.html.twig';
import homepagePubllicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';
import featuredContentCollection from '@theme/data/featured-content-collection';
import siteFooter from '@theme/data/site-footer.data.js';
import siteHeader from '@theme/data/site-header.data.js';

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

export const homepagePubllic = () =>
  homepagePubllicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    common: common,
    featured_events: Object.assign(featuredContentCollection.event, {
      extra_classes:
        'ecl-featured-content-collection--is-compact ecl-section-wrapper ecl-section-wrapper--is-blue',
    }),
  });

export default {
  title: 'Pages / Homepage',
};
