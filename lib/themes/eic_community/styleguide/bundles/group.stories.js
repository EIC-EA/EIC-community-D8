export default {
  title: 'Bundles / Group',
};

import LinkTemplate from '@ecl-twig/ec-component-link/ecl-link.html.twig';

import AboutFullTemplate from '@theme/patterns/compositions/group/group.full--about.html.twig';
import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';
import DiscussionFullTemplate from '@theme/patterns/compositions/group/group.full--discussion.html.twig';
import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';
import EventTeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import ExtendedListTemplate from '@theme/patterns/compositions/extended-list/extended-list.html.twig';
import MemberFullTemplate from '@theme/patterns/compositions/group/group.full--member.html.twig';
import MemberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import FrontpageTemplate from '@theme/patterns/compositions/group/group.full--frontpage.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';
import SimpleBannerTemplate from '@theme/patterns/compositions/simple-banner.html.twig';
import StateBannerTemplate from '@theme/patterns/compositions/state-banner.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import WikiFullTemplate from '@theme/patterns/compositions/group/group.full--wiki.html.twig';
import WysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';

import attachment from '@theme/data/attachment.data';
import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import comments from '@theme/data/comments.data';
import discussionThread from '@theme/data/discussion-thread.data';
import extendedList from '@theme/data/extended-list';
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
import wiki from '@theme/data/wiki';

import { editableField, mockItems, without } from '@theme/snippets';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  mainmenu: mainmenu,
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

export const Teaser = () => TeaserTemplate(teaser.group);

const GroupOverview = (props) =>
  FrontpageTemplate(
    Object.assign(
      Object.assign(
        {
          editorial_header: header,
          subnavigation: subnavigation.overview,
        },
        props
      ),
      overview
    )
  );

export const GroupOverviewPublic = () =>
  GroupOverview({
    discussions: featuredContentCollection.discussion,
    amount_options: teaserOverview.amount_options,
    active_filters: teaserOverview.active_filters,
    sort_options: teaserOverview.sort_options,
    sorting: sorting,
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
  });

export const GroupOverviewPending = () =>
  GroupOverview({
    content: `${StateBannerTemplate({
      icon_file_path: common.icon_file_path,
      icon: {
        name: 'warning',
        type: 'notifications',
      },
      title: 'Awaiting approval',
      description: `<p>This group is pending approval from our community team. We will endeavour to approve the group within 3 days.</p><p>You can still <a href="">manage</a> or <a href="">delete</a> your group.</p>`,
    })}`,
    editorial_header: Object.assign(
      {},
      header,
      {
        actions: [
          {
            link: {
              label: 'Delete this group',
              path: '#path=request'
            },
            icon: {
              name: 'bin',
              type: 'custom'
            },
            cta: true
          },
          {
            icon: {
              name: 'gear',
              type: 'custom'
            },
            link: {
              label: 'Settings',
              path: '#path=request'
            },
            is_compact: true,
          }
        ]
      }
    ),
    subnavigation: Object.assign(
      {},
      subnavigation.overview,
      {
        items:[
          {
            link: {
              label: 'Overview',
            },
            is_active: true,
          },
          {
            link: {
              label: 'Latest Activity',
            },
          },
          {
            link: {
              label: 'Disussions',
            },
          },
          {
            link: {
              label: 'About',
            },
          }
        ]
      }
    )
  });

export const GroupOverviewDraft = () =>
  GroupOverview({
    content: `
        ${StateBannerTemplate({
          extra_classes: 'ecl-state-banner--is-blue',
          icon_file_path: common.icon_file_path,
          icon: {
            name: 'success',
            type: 'notifications',
          },
          title: 'Congratulations!  Group approved',
          description: `
            <p>We advise these steps to make your group ready:</p>
            <ul>
              <li><a href="">Configure your group</a>: configure a welcome message for your GM’s, configure the content sections</li>
              <li><a href="">Create content</a> in the group</li>
              <li><a href="#">Publish your group</a> in the group</li>
            </ul>
            <p><a href="#">Click here</a> if you need additional help.</p>
          `,
          dismiss: {
            link: {
              path: '#close',
            },
          },
        })}
        ${SimpleBannerTemplate({
          title: 'Get the conversation started',
          description: 'Start discussions with other members.',
          actions: [
            {
              label: 'Post Content',
              items: [
                {
                  link: {
                    label: 'New Story',
                  },
                },
                {
                  link: {
                    label: 'New Wiki',
                  },
                },
              ],
            },
          ],
        })}
      `,
  });

export const GroupOverviewPublished = () =>
  GroupOverview({
    content: `
        ${StateBannerTemplate({
          extra_classes: 'ecl-state-banner--is-blue',
          icon_file_path: common.icon_file_path,
          icon: {
            name: 'group-approved',
            type: 'custom',
          },
          title: 'Great! Your group is published. Now invite people to the group.',
          description: `
            <p><a href="">Invite</a> users so they can contribute and engage with your group.<br/><a href="">Click here</a> if you want to make any changes to the settings of your group.</p>
          `,
          dismiss: {
            link: {
              path: '#close',
            },
          },
        })}
        ${SimpleBannerTemplate({
          title: 'Get the conversation started',
          description: 'Start discussions with other members.',
          actions: [
            {
              label: 'Post Content',
              items: [
                {
                  link: {
                    label: 'New Story',
                  },
                },
                {
                  link: {
                    label: 'New Wiki',
                  },
                },
              ],
            },
          ],
        })}
      `,
  });

export const GroupOverviewPrivate = () =>
  FrontpageTemplate(
    Object.assign(
      {
        editorial_header: Object.assign(
          {
            actions: [
              {
                label: 'Post content',
                items: [
                  {
                    label: 'Start Discussion',
                  },
                ],
              },
              {
                icon: {
                  name: 'gear',
                  type: 'custom',
                },
                label: 'Settings',
                is_compact: true,
              },
            ],
            flags: header.flags.concat([
              {
                link: {
                  label: 'Invite a member',
                },
                icon: {
                  name: 'invite',
                  type: 'custom',
                },
              },
              {
                link: {
                  label: 'Leave this group',
                },
                icon: {
                  name: 'leave',
                  type: 'custom',
                },
              },
            ]),
          },
          without(header, 'actions', 'flags')
        ),
        discussions: featuredContentCollection.discussion,
        amount_options: teaserOverview.amount_options,
        active_filters: teaserOverview.active_filters,
        sort_options: teaserOverview.sort_options,
        subnavigation: subnavigation.discussion,
        sorting: sorting,
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
      overview
    )
  );

export const GroupDiscussionPublic = () =>
  DiscussionFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        filters: filters.groupDiscussion,
        amount_options: teaserOverview.amount_options,
        active_filters: teaserOverview.active_filters,
        sort_options: teaserOverview.sort_options,
        sorting: sorting,
        subnavigation: subnavigation.discussion,
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
                  featured: {
                    items: [without(comments.items[0], 'items')],
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
        filters: filters.groupDiscussion,
        subnavigation: subnavigation.discussion,
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

export const GroupMemberPrivate = () =>
  MemberFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        filters: filters.groupMember,
        amount_options: teaserOverview.amount_options,
        active_filters: teaserOverview.active_filters,
        sort_options: teaserOverview.sort_options,
        subnavigation: subnavigation.member,
        items: mockItems(
          {
            content: MemberTemplate(
              Object.assign(
                {
                  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                },
                teaser.member
              )
            ),
          },
          12
        ),
      },
      overview
    )
  );

export const GroupWikiPublic = () =>
  WikiFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        filters: filters.groupWiki,
        subnavigation: subnavigation.wiki,
        items: [],
        no_items: {
          link: {
            label: 'Add a new wiki page',
            path: '#',
          },
        },
      },
      overview
    )
  );

export const GroupWikiPrivate = () =>
  WikiFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        filters: filters.groupWiki,
        subnavigation: subnavigation.wiki,
        new_wiki: LinkTemplate({
          link: {
            label: 'Add a new wiki page',
            path: '#new-wiki',
          },
          extra_classes: 'ecl-link--button ecl-link--has-full-width ecl-link--button-primary',
        }),
        contributors: TeaserOverviewTemplate({
          title: 'Contributors',
          title_element: 'h4',
          extra_classes: 'ecl-teaser-overview--has-compact-layout',
          items: [
            {
              content: MemberTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                  },
                  teaser.member
                )
              ),
            },
          ],
        }),
        actions: {
          items: [
            {
              link: {
                label: 'Add page on same level',
                path: '?path=current',
              },
            },
            {
              link: {
                label: 'Add child page',
                path: '?path=new',
              },
            },
          ],
        },
        items: wiki.items,
        content: WysiwygTemplate(),
        no_items: {
          link: {
            label: 'Add a new wiki page',
            path: '#',
          },
        },
      },
      overview
    )
  );

export const GroupAboutPublic = () =>
  AboutFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        subnavigation: subnavigation.about,
        items: [
          {
            title: 'About this group',
            content: ExtendedListTemplate(extendedList.group),
          },
        ],
      },
      overview
    )
  );

export const GroupAboutPrivate = () =>
  AboutFullTemplate(
    Object.assign(
      {
        editorial_header: header,
        subnavigation: subnavigation.about,
        content: WysiwygTemplate(),
        items: [
          {
            title: 'About this group',
            content: ExtendedListTemplate(extendedList.group),
          },
          {
            title: 'Permission and visibility',
            content: ExtendedListTemplate(extendedList.groupPermissions),
          },
        ],
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
      10,
      () => ({ content: TeaserTemplate(without(teaser.group, 'image')) }),
      [0]
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
