export default {
  title: 'Bundles / Group',
};

import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import OverviewFullTemplate from '@theme/patterns/compositions/group/group.full--overview.html.twig';
import DiscussionFullTemplate from '@theme/patterns/compositions/group/group.full--discussion.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';
import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import EventTeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';

import attachment from '@theme/data/attachment.data';
import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import comments from '@theme/data/comments.data';
import discussionThread from '@theme/data/discussion-thread.data';
import featuredContentCollection from '@theme/data/featured-content-collection';
import featuredContentSections from '@theme/data/featured-content-sections';
import filters from '@theme/data/filters';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import sorting from '@theme/data/sorting.data';
import subnavigation from '@theme/data/subnavigation';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topMenu from '@theme/data/top-menu.data';

import { editableField, mockItems, without } from '@theme/snippets';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  mainmenu: mainmenu,
  amount_options: teaserOverview.amount_options,
  active_filters: teaserOverview.active_filters,
  sort_options: teaserOverview.sort_options,
  site_footer: siteFooter,
  site_header: siteHeader,
  top_menu: topMenu,
  filters: filters.groupDiscussion,
  sorting: sorting,
  pagination: pagination,
  subnavigation: subnavigation.discussion,
};

const header = {
  icon_file_path: common.icon_file_path,
  description: editableField(),
  title: 'The big debate about the Climate',
  image: {
    src: 'http://picsum.photos/320/160',
  },
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
      label: '5',
      icon: {
        name: 'like',
        type: 'custom',
      },
    },
    {
      label: '24',
      icon: {
        name: 'follow',
        type: 'custom',
      },
    },
    {
      link: {
        label: 'Request to join the group',
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

export const Teaser = () => TeaserTemplate(teaser.group);

export const GroupOverviewPublic = () =>
  OverviewFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        discussions: featuredContentCollection.discussion,
        attachments: {
          title: 'Documents',
          items: mockItems(
            {
              content: AttachmentTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-attachment--has-compact-layout',
                  },
                  attachment
                )
              ),
            },
            4,
            () => ({
              content: AttachmentTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-attachment--has-compact-layout',
                    highlight: {
                      is_active: true,
                    },
                  },
                  attachment
                )
              ),
            }),
            [2]
          ),
        },
        events: featuredContentCollection.event,
        related_stories: featuredContentCollection.story,
        related_groups: featuredContentCollection.group,
        latest_tags: {
          title: 'Latest content for you',
          items: mockItems(
            {
              label: 'Duis quis',
              path: '#',
            },
            7,
            () => ({
              label: 'Cupidatat aliqua consequat laboris',
              path: '#',
              is_active: true,
            }),
            [2]
          ),
          collapse_label: 'More topics',
        },
        subnavigation: subnavigation.overview,
      },
      without(overview, 'subnavigation')
    )
  );

export const GroupDiscussionPublic = () =>
  DiscussionFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        items: mockItems(
          {
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  highlight: {
                    is_active: false,
                  },
                },
                discussionThread
              )
            ),
          },
          12,
          () => ({
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  highlight: {
                    is_active: true,
                  },
                },
                discussionThread
              )
            ),
          }),
          [1, 3]
        ),
      },
      overview
    )
  );

export const GroupDiscussionPrivate = () =>
  DiscussionFullTemplate(
    Object.assign(
      {
        user: common.user,
        editorial_header: {
          icon_file_path: common.icon_file_path,
          description: editableField(),
          title: 'The big debate about the Climate',
          image: {
            src: 'http://picsum.photos/320/160',
          },
          tags: [
            {
              tag: {
                label: 'Private',
              },
              extra_classes: 'ecl-tag--is-private',
            },
          ],
          actions: [
            {
              label: '5',
              icon: {
                name: 'like',
                type: 'custom',
              },
            },
            {
              label: '24',
              icon: {
                name: 'follow',
                type: 'custom',
              },
            },
            {
              label: 'Post content',
              items: [
                {
                  label: 'Start Discussion',
                },
              ],
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
        },
        items: mockItems(
          {
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  highlight: {
                    is_active: false,
                  },
                },
                discussionThread
              )
            ),
          },
          12,
          () => ({
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  highlight: {
                    is_active: true,
                  },
                },
                discussionThread
              )
            ),
          }),
          [1, 3]
        ),
      },
      overview
    )
  );

export const OverviewPublic = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    mainmenu: mainmenu,
    overview_header: {
      title: 'Groups',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.group,
    items: mockItems(
      {
        content: TeaserTemplate(teaser.group),
      },
      10
    ),
    pagination: pagination,
  });

export const OverviewPrivate = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    top_menu: topMenu,
    user: common.user,
    mainmenu: mainmenu,
    overview_header: {
      title: 'Groups',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
      actions: [
        {
          label: 'New Group',
          path: '?action=new-group',
        },
      ],
    },
    filter_title: 'Filter',
    filters: filters.group,
    items: mockItems(
      {
        content: TeaserTemplate(teaser.group),
      },
      10
    ),
    pagination: pagination,
  });
