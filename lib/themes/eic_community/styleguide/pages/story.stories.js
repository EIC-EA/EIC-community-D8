export default {
  title: 'Pages / Story',
};

import editorialHeader from '@theme/data/editorial-header.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import storyFull from '@theme/patterns/pages/story/story.full.html.twig';
import storyOverview from '@theme/patterns/pages/story/story.overview.html.twig';
import editorialHeaderData from '../../data/editorial-header.data';

export const Detail = () =>
  storyFull({
    site_footer: siteFooter,
    site_header: siteHeader,
    editorial_header: editorialHeader,
  });
export const Overview = () =>
  storyOverview({
    site_footer: siteFooter,
    site_header: siteHeader,
  });
