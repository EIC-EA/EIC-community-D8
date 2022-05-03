import FullTemplate from '@theme/patterns/compositions/discussion/discussion.full.html.twig';
import wysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';

import author from '@theme/data/author.data';
import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import topics from '@theme/data/topics.data';

const defaults = (user) => ({
  common: common,
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
  editorial_header: {
    icon_file_path: common.icon_file_path,
    title: 'Discussion about Amet laborum officia exercitation laboris exercitation esse.',
    type: {
      label: 'Idea',
      icon: {
        name: 'idea',
        type: 'custom',
      },
    },
    parent: {
      link: {
        label: 'All discussions',
        path: '?path=groups',
      },
    },
    actions: [
      !user && {
        link: {
          label: 'Login to join group',
          path: '#path=request',
        },
      },
    ],
  },
  topics: topics,
  flags: {
    icon_file_path: common.icon_file_path,
    items: user
      ? [
          {
            icon: {
              name: 'tag',
              type: 'custom',
            },
            link: {
              label: 'Bookmarked',
            },
          },
          {
            icon: {
              name: 'like',
              type: 'custom',
            },
            link: {
              label: 'Like (5)',
            },
          },
          {
            link: {
              label: 'Add to highlight',
            },
            icon: {
              type: 'custom',
              name: 'star_circle',
            },
          },
        ]
      : [
          {
            icon: {
              name: 'like',
              type: 'custom',
            },
            link: {
              label: 'Like (5)',
            },
          },
        ],
  },
});

export const FullPrivate = () =>
  FullTemplate(
    Object.assign(
      {
        user: common.user,
        comments: comments,
      },
      defaults(author)
    )
  );

export const FullPublic = () => FullTemplate(defaults());

export default {
  title: 'Bundles / Discussion',
};
