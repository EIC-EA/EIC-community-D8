export default {
  title: 'Pages / Overview Page',
};

import overviewPageTemplate from '@theme/patterns/pages/overview-page.html.twig';

import siteHeader from '@theme/data/site-header.data.js';

export const Base = () =>
  overviewPageTemplate({
    site_header: siteHeader,
  });
