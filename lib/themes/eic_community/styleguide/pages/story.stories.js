export default {
  title: 'Pages / Story',
};

import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import pagination from '@theme/data/pagination.data';

import storyOverview from '@theme/patterns/pages/story/story.overview.html.twig';
import storyTeaser from '@theme/patterns/compositions/story.teaser.html.twig';

import common from '@theme/data/common.data';
import story from '@theme/data/story.data';
import filters from '@theme/data/filters.data';

export const Overview = () =>
  storyOverview({
    common: common,
    overview_header: {
      title: 'Stories',
    },
    filter_title: 'Filter',
    filters: filters.story,
    items: [
      {
        content: storyTeaser(story),
      },
      {
        content: storyTeaser(story),
      },
      {
        content: storyTeaser(story),
      },
    ],
    pagination: pagination,
    site_footer: siteFooter,
    site_header: siteHeader,
  });
