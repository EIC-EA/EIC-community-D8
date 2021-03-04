import FullTemplate from '@theme/patterns/compositions/discussion/discussion.full.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import common from '@theme/data/common.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import topics from '@theme/data/topics.data';

import wysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';

export const FullPublic = () =>
  FullTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    featured_news: featuredContentSections.story,
    content: wysiwygTemplate({
      simple: true,
    }),
    editorial_header: {
      icon_file_path: common.icon_file_path,
      title: 'Discussion about Amet laborum officia exercitation laboris exercitation esse.',
      tags: [
        {
          tag: {
            label: 'Public',
          },
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
