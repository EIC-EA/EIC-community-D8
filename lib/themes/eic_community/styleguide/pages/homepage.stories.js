import homepagePrivateTemplate from '@theme/patterns/pages/homepage/homepage.private.html.twig';
import homepagePublicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import cardOverview from '@theme/data/card-overview.data';

export const HomepagePublic = () =>
  homepagePublicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    common: common,
    usp: {
      title: 'As an EIC beneficiary, access a one-stop-shop',
      description:
        'Exercitation laboris exercitation cillum aliqua commodo reprehenderit voluptate non mollit in eu nisi ipsum adipisicing.',
      items: cardOverview.items,
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
