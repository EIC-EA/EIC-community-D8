import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';
import FullTemplate from '@theme/patterns/compositions/discussion/discussion.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/discussion/discussion.overview.html.twig';
import WysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';

import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import discussionThread from '@theme/data/discussion-thread.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import filters from '@theme/data/filters.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import sorting from '@theme/data/sorting.data';
import subnavigation from '@theme/data/subnavigation';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topics from '@theme/data/topics.data';
import topMenu from '@theme/data/top-menu.data';

import { editableField, mockItems } from '@theme/snippets';

export const Teaser = () => DiscussionThreadTemplate(discussionThread);

export const FullPublic = () =>
  FullTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    featured_news: featuredContentSections.story,
    content: WysiwygTemplate({
      simple: true,
    }),
    author: author,
    timestamp: {
      label: '3 days ago',
    },
    editorial_header: {
      icon_file_path: common.icon_file_path,
      title: 'Discussion about Amet laborum officia exercitation laboris exercitation esse.',
      tags: [
        {
          tag: {
            label: 'Public',
          },
          extra_classes: 'ecl-tag--is-public',
        },
      ],
      parent: {
        link: {
          label: 'All groups',
          path: '?path=groups',
        },
      },
    },
    topics: topics,
  });

export default {
  title: 'Bundles / Discussion',
};
