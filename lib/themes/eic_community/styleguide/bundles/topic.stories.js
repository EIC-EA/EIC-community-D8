import {event_infos_items} from "../../data/event.data";

export default {
  title: 'Bundles / Topic',
};


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


export const TopicOverview = () =>
  FrontpageTemplate(
    Object.assign(
      {
        title: 'Subtopic name',
        image: {
          src: 'https://picsum.photos/1600/400',
        },
        topics: topics,
        contributors: contributors,
        activityStream: activityStream,
        related_groups: Object.assign(featuredContentCollection.group, {
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
          items: mockItems(
            {
              content: TeaserTemplate(
                Object.assign(
                  {
                    extra_classes: '',
                  },
                  teaser.story
                )
              ),
            },
            2
          ),
        })
      },
      overview
    )
  );

