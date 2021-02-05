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

export default {
  title: 'Pages / Homepage',
};
