export default {
  title: 'Bundles / Story',
};

import FullTemplate from '@theme/patterns/compositions/story/story.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/story/story.overview.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';
import contributors from '@theme/data/contributors.data';
import editorialHeader from '@theme/data/editorial-header.data';
import filters from '@theme/data/filters';
import hero from '@theme/data/hero.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
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
  FullTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
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
  FullTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
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
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    mainmenu: mainmenu,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
    overview_header: {
      title: 'Stories',
    },
    filter_title: 'Filter',
    filters: filters.story,
    items: mockItems(
      {
        content: TeaserTemplate(teaser.story),
      },
      10
    ),
    pagination: pagination,
  });

export const OverviewPrivate = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    mainmenu: mainmenu,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
    user: common.user,
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
        content: TeaserTemplate(teaser.story),
      },
      10
    ),
    pagination: pagination,
  });

export const Teaser = () => TeaserTemplate(teaser.story);
