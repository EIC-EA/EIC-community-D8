export default {
  title: 'Bundles / Story',
};

import fullTemplate from '@theme/patterns/compositions/story/story.full.html.twig';
import overviewTemplate from '@theme/patterns/compositions/story/story.overview.html.twig';
import teaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';
import contributors from '@theme/data/contributors.data';
import editorialHeader from '@theme/data/editorial-header.data';
import filters from '@theme/data/filters.data';
import hero from '@theme/data/hero.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import socialShare from '@theme/data/social-share.data';
import stories from '@theme/data/stories.data';
import topics from '@theme/data/topics.data';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import wysiwygExample from '@theme/snippets/wysiwyg-example.html.twig';

import { mockItems } from '@theme/snippets';

export const FullPublic = () =>
  fullTemplate({
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
    comments: comments,
    social_share: socialShare,
    stories: stories,
    topics: topics,
    content: wysiwygExample(common),
  });

export const FullPrivate = () =>
  fullTemplate({
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
    auth: common.user,
    comments: comments,
    social_share: socialShare,
    stories: stories,
    topics: topics,
    content: wysiwygExample(common),
  });

export const OverviewPublic = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    overview_header: {
      title: 'Stories',
    },
    filter_title: 'Filter',
    filters: filters.story,
    items: mockItems(
      {
        content: teaserTemplate(teaser.story),
      },
      10
    ),
    pagination: pagination,
  });

export const OverviewPrivate = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    overview_header: {
      title: 'Stories',
    },
    filter_title: 'Filter',
    filters: filters.story,
    amount_options: teaserOverview.amount_options,
    active_filters: teaserOverview.active_filters,
    sort_options: teaserOverview.sort_options,
    items: mockItems(
      {
        content: teaserTemplate(teaser.story),
      },
      10
    ),
    pagination: pagination,
  });

export const Teaser = () => teaserTemplate(teaser.story);
