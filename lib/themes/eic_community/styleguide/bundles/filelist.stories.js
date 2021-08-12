export default {
  title: 'Bundles / Filelist',
};

import FullTemplate from '@theme/patterns/compositions/filelist/filelist.full.html.twig';
import FilelistTemplate from '@theme/patterns/components/filelist.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';
import contributors from '@theme/data/contributors.data';
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
import topMenu from '@theme/data/top-menu.data';
import filelist from '@theme/data/filelist.data';
import wysiwygExample from '@theme/snippets/wysiwyg-example.html.twig';

import { mockItems } from '@theme/snippets';

let filelistDetail = {
  common: common,
  site_footer: siteFooter,
  site_header: siteHeader,
  searchform: searchform,
  editorial_header: {
    icon_file_path: common.icon_file_path,
    parent: {
      link: {
        label: 'Previous page',
        path: '#',
      },
    },
  },
  hero: hero,
  breadcrumb: breadcrumb,
  editorial_actions: {
    icon_file_path: common.icon_file_path,
    items: [
      {
        link: {
          label: 'Liked (27)',
          path: '?path=like',
        },
        icon: {
          type: 'custom',
          name: 'like',
        },
      }
    ],
  },
  contributors: contributors,
  comments: comments,
  social_share: socialShare,
  topics: topics,
  content: FilelistTemplate(filelist),
};

export const FullPublic = () => FullTemplate(filelistDetail);

export const FullPrivate = () => FullTemplate(
  Object.assign(
    {},
    filelistDetail,
    {
      user: common.user,
      editorial_actions: {
        icon_file_path: common.icon_file_path,
        items: [
          {
            link: {
              label: 'Bookmarked',
              path: '?path=bookmark',
            },
            icon: {
              type: 'custom',
              name: 'tag',
            },
          },
          {
            link: {
              label: 'Liked (27)',
              path: '?path=like',
            },
            icon: {
              type: 'custom',
              name: 'like',
            },
          },
          {
            link: {
              label: 'Add to highlight',
              path: '?path=login',
            },
            icon: {
              type: 'custom',
              name: 'star_circle',
            },
          }
        ],
      },
    }
  )
)
