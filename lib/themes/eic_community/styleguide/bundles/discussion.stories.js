import FullTemplate from '@theme/patterns/compositions/discussion/discussion.full.html.twig';

import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import wysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';

export const FullPrivate = () =>
  FullTemplate({
    common: common,
    comments: comments,
    site_footer: siteFooter,
    site_header: siteHeader,
    featured_news: featuredContentSections.story,
    content: wysiwygTemplate({
      simple: true,
    }),
    author: author,
    timestamp: {
      label: '3 days ago',
    },
    user: common.user,
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
  });

export default {
  title: 'Bundles / Discussion',
};
