export default {
  title: 'Pages / Story',
};

import common from '@theme/data/common.data';
import comments from '@theme/data/comments.data';
import contributors from '@theme/data/contributors.data';
import editorialArticle from '@theme/data/editorial-article.data';
import editorialHeader from '@theme/data/editorial-header.data';
import hero from '@theme/data/hero.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import stories from '@theme/data/stories.data';
import topics from '@theme/data/topics.data';
import socialShare from '@theme/data/social-share.data';

import storyFull from '@theme/patterns/pages/story/story.full.html.twig';
import storyOverview from '@theme/patterns/pages/story/story.overview.html.twig';
import editorialHeaderData from '../../data/editorial-header.data';

import wysiwygExample from '@theme/snippets/wysiwyg-example.html.twig';

export const FullPublic = () =>
  storyFull({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    editorial_header: editorialHeader,
    hero: hero,
    contributors: contributors,
    comments: [],
    social_share: socialShare,
    stories: stories,
    topics: topics,
    content: wysiwygExample(common),
  });

export const Overview = () =>
  storyOverview({
    site_footer: siteFooter,
    site_header: siteHeader,
  });
