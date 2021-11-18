export default {
  title: 'Bundles / Topic',
};

import activityStream from '@theme/data/activity-stream.data.js';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import contributors from '@theme/data/contributors.data.js';
import featuredContentCollection from '@theme/data/featured-content-collection';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';
import topics from '@theme/data/topics.data';
import topMenu from '@theme/data/top-menu.data';

import FilelistTeaserTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';
import FrontDetailsTemplate from '@theme/patterns/compositions/topic/topic.details.html.twig';
import FrontOverviewTemplate from '@theme/patterns/compositions/topic/topic.overview.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import { mockItems, randomString } from '@theme/snippets';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  mainmenu: mainmenu,
  searchform: searchform,
  site_footer: siteFooter,
  site_header: siteHeader,
  top_menu: topMenu,
  pagination: pagination,
};
const overviewItem = {
  "title": randomString(),
  "url": "#",
  "image": {
    src: 'https://picsum.photos/1600/400',
  },
  stats: {
    groups: {
      stat: 10,
      icon: {
        type: "custom",
        name: "group"
      }
    },
    experts: {
      stat: 3,
      icon: {
        type: "custom",
        name: "user_circle"
      }
    }
  }
}

export const TopicDetails = () =>
  FrontDetailsTemplate(
    Object.assign(
      {
        title: 'Subtopic name',
        image: {
          src: 'https://picsum.photos/1600/400',
        },
        body: "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
        icon_file_path: common.icon_file_path,
        stats: mockItems(
          {
            title: "Members",
            data: "123",
            icon: {
              name: 'like',
              type: 'custom',
            },
          },
          4
        ),
        editorial_header: {
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'Previous page',
              path: '#',
            },
          },
        },
        topics: topics,
        contributors: Object.assign(
          contributors,
          {
            is_collapsible: true,
            title: "Experts"
          }
        ),
        content: "",
        activityStream: {...activityStream, title: "Related Activity"},
        related_groups: Object.assign(featuredContentCollection.group, {
          title: "Related groups",
          items: mockItems(
            {
              content: TeaserTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                  },
                  teaser.group
                )
              ),
            },
            2
          ),
        } ),
        related_companies: featuredContentCollection.companies,
        related_stories: Object.assign(featuredContentCollection.story, {
          call_to_action: {
            link: {
              label: 'Show more',
            },
          },
          items: mockItems(
            {
              content: TeaserTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-teaser--grey',
                  },
                  teaser.story
                )
              ),
            },
            2
          ),
        }),
        discussions: Object.assign(featuredContentCollection.discussion,{
          title: "Related discussions",
          call_to_action: {
            link: {
              label: 'Show more',
            },
          },
        }),
        related_documents: {
          title: 'Related files',
          call_to_action: {
            link: {
              label: 'View all files',
            },
          },
          items: mockItems(
            {
              content: FilelistTeaserTemplate({extra_classes: "ecl-teaser--grey", ...teaser.filelist}),
            },
            4
          ),
        },
        events: {...featuredContentCollection.event, title: "Related events"},
      },
      overview
    )
  );

export const TopicOverview = () =>
  FrontOverviewTemplate(
    Object.assign(
      {
        editorial_header: {
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'Previous page',
              path: '#',
            },
          },
        },
        title: 'Topic overview',
        image: {
          src: 'https://picsum.photos/1600/400',
        },
        body: "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
        icon_file_path: common.icon_file_path,
        topics: [
          {
            title: "Horizontal",
            items: mockItems(overviewItem, 10)
          },
          {
            title: "Topic",
            items:  mockItems(overviewItem, 8)
          }
        ],
      },
      overview
    )
  );

