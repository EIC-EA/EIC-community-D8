export default {
  title: 'Bundles / Story',
};

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import comments from '@theme/data/comments.data';
import contributors from '@theme/data/contributors.data';
import editorialHeader from '@theme/data/editorial-header.data';
import hero from '@theme/data/hero.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import stories from '@theme/data/stories.data';
import topics from '@theme/data/topics.data';
import socialShare from '@theme/data/social-share.data';

import wysiwygExample from '@theme/snippets/wysiwyg-example.html.twig';

import full from '@theme/patterns/compositions/story/story.full.html.twig';
import overview from '@theme/patterns/compositions/story/story.overview.html.twig';
import teaser from '@theme/patterns/compositions/story/story.teaser.html.twig';

export const FullPublic = () =>
  full({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    editorial_header: editorialHeader,
    hero: hero,
    breadcrumb: breadcrumb,
    editorial_actions: {
      icon_file_path: common.icon_file_path,
      items: [
        {
          extra_classes: 'ecl-link--guest',
          link: {
            label: 'Recommend (27)',
            path: '?path=login',
          },
          icon: {
            type: 'custom',
            name: 'like',
          },
        },
      ],
    },
    contributors: contributors,
    comments: [],
    social_share: socialShare,
    stories: stories,
    topics: topics,
    content: wysiwygExample(common),
  });

export const OverviewPublic = () =>
  overview({
    site_footer: siteFooter,
    site_header: siteHeader,
  });

export const Teaser = () =>
  teaser({
    title: 'Aliquip aliquip non sint mollit velit minim proident ipsum eu pariatur id ullamco.',
  });
