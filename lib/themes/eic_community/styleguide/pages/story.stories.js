export default {
  title: 'Pages / Story',
};

import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import storyOverview from '@theme/patterns/pages/story/story.overview.html.twig';

export const Overview = () =>
  storyOverview({
    site_footer: siteFooter,
    site_header: siteHeader,
  });
