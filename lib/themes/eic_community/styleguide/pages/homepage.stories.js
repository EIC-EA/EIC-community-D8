import homepagePrivateTemplate from '@theme/patterns/compositions/homepage/homepage.private.html.twig';
import homepagePublicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';
import fact_figures from '@theme/data/fact-figures.data';
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

export const HomepagePublic = () =>
  homepagePublicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    common: common,
    fact_figures: fact_figures,
  });

export default {
  title: 'Pages / Homepage',
};
