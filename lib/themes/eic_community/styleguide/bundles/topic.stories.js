import {event_infos_items} from "../../data/event.data";

export default {
  title: 'Bundles / Topic',
};

import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';
import attachment from '@theme/data/attachment.data';

import FilelistTeaserTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';

import FrontpageTemplate from '@theme/patterns/compositions/topic/topic.overview.html.twig';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import topMenu from '@theme/data/top-menu.data';
import topics from '@theme/data/topics.data';
import contributors from '@theme/data/contributors.data.js';
import featuredContentCollection from '@theme/data/featured-content-collection';
import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';

import teaser from '@theme/data/teaser';

import activityStream from '@theme/data/activity-stream.data.js';

import siteHeader from '@theme/data/site-header.data';

import { editableField, mockItems, without } from '@theme/snippets';

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

const header = {
  icon_file_path: common.icon_file_path,
  description: editableField(),
  title: 'The big debate about the Climate',
  image: {
    src: 'http://picsum.photos/320/160',
  },
  flags: [
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
      icon: {
        name: 'follow',
        type: 'custom',
      },
      link: {
        label: 'Follow (24)',
      },
    },
  ],
  tags: [
    {
      tag: {
        label: 'Public',
      },
      extra_classes: 'ecl-tag--is-public',
    },
  ],
  actions: [
    {
      link: {
        label: 'Login to join group',
        path: '#path=request',
      },
    },
  ],
  stats: [
    {
      label: 'Members',
      path: '#foo',
      value: 294,
      icon: {
        name: 'group',
        type: 'custom',
      },
      updates: {
        label: 'Latest members from the past 14 days.',
        value: 14,
      },
    },
    {
      label: 'Comments',
      path: '#foo',
      value: 33,
      icon: {
        name: 'comment',
        type: 'custom',
      },
    },
    {
      label: 'Attachments',
      value: 4,
      icon: {
        name: 'documents',
        type: 'custom',
      },
    },
    {
      label: 'events',
      value: 2,
      icon: {
        name: 'calendar',
        type: 'custom',
      },
    },
  ],
  parent: {
    link: {
      label: 'All Groups',
      path: '?path=groups',
    },
  },
};

const topicDetails = {
  title: 'Subtopic name',
  body: '<p>Lorem ipsum dolor sit amet, facilisi vulputate ne sea, quod tamquam eam ex. Ut inani nostrud torquatos eam. Et hinc graeco facete his. Ea nibh eleifend sea, quo praesent expetenda conceptam cu. Regione tritani vim in, eam eu mundi adolescens. At est decore persius accusata.</p><p>Denique hendrerit delicatissimi no quo, eu quo suas facer putent, ex fastidii placerat senserit qui. At qui diam phaedrum, ex usu lucilius petentium neglegentur. Nam no elitr consulatu adversarium, his graeco euismod alienum an. Putent dignissim sea id, usu ei zril prompta recteque. Malis delectus ut sea. Pro id epicuri probatus convenire.</p>',
}

export const TopicDetails = () =>
  FrontpageTemplate(
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

