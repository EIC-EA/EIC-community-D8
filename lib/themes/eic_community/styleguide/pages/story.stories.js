export default {
  title: 'Pages / Story',
};

import comments from '@theme/data/comments.data';
import contributors from '@theme/data/contributors.data';
import editorialArticle from '@theme/data/editorial-article.data';
import editorialHeader from '@theme/data/editorial-header.data';
import hero from '@theme/data/hero.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import storyFull from '@theme/patterns/pages/story/story.full.html.twig';
import storyOverview from '@theme/patterns/pages/story/story.overview.html.twig';
import editorialHeaderData from '../../data/editorial-header.data';

export const Full = () =>
  storyFull({
    site_footer: siteFooter,
    site_header: siteHeader,
    editorial_header: editorialHeader,
    hero: hero,
    contributors: contributors,
    comments: comments,
  });
export const Overview = () =>
  storyOverview({
    site_footer: siteFooter,
    site_header: siteHeader,
  });
