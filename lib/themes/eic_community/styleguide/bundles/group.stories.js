import { event_infos_items } from "../../data/event.data";

export default {
  title: 'Bundles / Group',
};

import LinkTemplate from '@ecl-twig/ec-component-link/ecl-link.html.twig';

import ActivityFullTemplate from '@theme/patterns/compositions/group/group.full--activity.html.twig';
import AboutFullTemplate from '@theme/patterns/compositions/group/group.full--about.html.twig';
import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';
import DiscussionFullTemplate from '@theme/patterns/compositions/group/group.full--discussion.html.twig';
import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';
import ExtendedListTemplate from '@theme/patterns/compositions/extended-list/extended-list.html.twig';
import FileListFullTemplate from '@theme/patterns/compositions/filelist/filelist.full.html.twig';
import FilelistTemplate from '@theme/patterns/components/filelist.html.twig';
import MemberFullTemplate from '@theme/patterns/compositions/group/group.full--member.html.twig';
import MemberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import FrontpageTemplate from '@theme/patterns/compositions/group/group.full--frontpage.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';
import SimpleBannerTemplate from '@theme/patterns/compositions/simple-banner.html.twig';
import StateBannerTemplate from '@theme/patterns/compositions/state-banner.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import WikiFullTemplate from '@theme/patterns/compositions/group/group.full--wiki.html.twig';
import WysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';
import DocumentFullTemplate from '@theme/patterns/compositions/group/group.full--document.html.twig';
import FilelistTeaserTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';
import GalleryTeaserTemplate from '@theme/patterns/compositions/gallery/gallery.teaser.html.twig';
import GroupEventFullTemplate from '@theme/patterns/compositions/group/group-event.full.html.twig';
import VideoTeaserTemplate from '@theme/patterns/compositions/video/video.teaser.html.twig';
import VideoFullTemplate from '@theme/patterns/compositions/video/video.full.html.twig';
import VideoTemplate from '@theme/patterns/components/video.html.twig';
import EventTemplate from '@theme/patterns/components/event.html.twig';


import activity_stream from '@theme/data/activity-stream.data';
import attachment from '@theme/data/attachment.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import comments from '@theme/data/comments.data';
import contributors from '@theme/data/contributors.data';
import discussionThread from '@theme/data/discussion-thread.data';
import extendedList from '@theme/data/extended-list';
import event from '@theme/data/event.data';
import featuredContentCollection from '@theme/data/featured-content-collection';
import filelist from '@theme/data/filelist.data';
import filters from '@theme/data/filters';
import hero from '@theme/data/hero.data';
import mainmenu from '@theme/data/mainmenu.data';
import members from '@theme/data/members.data'
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import socialShare from '@theme/data/social-share.data';
import sorting from '@theme/data/sorting.data';
import subnavigation from '@theme/data/subnavigation';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topics from '@theme/data/topics.data';
import topMenu from '@theme/data/top-menu.data';
import video from '@theme/data/video.data';
import wiki from '@theme/data/wiki';

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
            type: 'cta'
          },
          {
            icon: {
              name: 'gear',
              type: 'custom',
            },
            variant: 'primary',
            label: 'Settings',
            is_compact: true
          }
        ],
        flags: [],
        stats: [
          {
            label: 'Members',
            path: '#foo',
            value: 1,
            icon: {
              name: 'group',
              type: 'custom',
            }
          },
          {
            label: 'Comments',
            path: '#foo',
            value: 0,
            icon: {
              name: 'comment',
              type: 'custom',
            },
          },
          {
            label: 'Attachments',
            value: 0,
            icon: {
              name: 'documents',
              type: 'custom',
            },
          },
          {
            label: 'events',
            value: 0,
            icon: {
              name: 'calendar',
              type: 'custom',
            },
          },
        ]
      }
    ),
    subnavigation: Object.assign(
      {},
      subnavigation.overview,
      {
        items: [
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
              label: 'Discussions',
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
              <li>Configure your group configure a welcome message for your GM’s, configure the content sections</li>
              <li>Create content in the group</li>
              <li>Publish your group in the group</li>
            </ul>
            <p><a href="#">Click here</a> if you need additional help.</p>
          `
    })}
        ${SimpleBannerTemplate({
      icon_file_path: common.icon_file_path,
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
          icon: {
            name: 'plus',
            type: 'ui',
          },
        },
      ],
    })}
      `,
    editorial_header: Object.assign(
      {},
      header,
      {
        actions: [
          {
            link: {
              label: 'Publish',
              path: '#path=request'
            },
            variant: 'secondary'
          },
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
          {
            icon: {
              name: 'gear',
              type: 'custom',
            },
            variant: 'primary',
            label: 'Settings',
            is_compact: true
          }
        ],
        flags: [],
        stats: [
          {
            label: 'Members',
            path: '#foo',
            value: 1,
            icon: {
              name: 'group',
              type: 'custom',
            }
          },
          {
            label: 'Comments',
            path: '#foo',
            value: 0,
            icon: {
              name: 'comment',
              type: 'custom',
            },
          },
          {
            label: 'Attachments',
            value: 0,
            icon: {
              name: 'documents',
              type: 'custom',
            },
          },
          {
            label: 'events',
            value: 0,
            icon: {
              name: 'calendar',
              type: 'custom',
            },
          },
        ]
      }
    ),
    subnavigation: Object.assign(
      {},
      subnavigation.overview,
      {
        items: [
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
              label: 'Discussions',
            },
          },
          {
            link: {
              label: 'About',
            },
          }
        ],
        searchform: searchform
      }
    )
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
          `
    })}
        ${SimpleBannerTemplate({
      icon_file_path: common.icon_file_path,
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
          icon: {
            name: 'plus',
            type: 'ui',
          },
        },
      ],
    })}
      `,
    editorial_header: Object.assign(
      {},
      header,
      {
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
          {
            icon: {
              name: 'gear',
              type: 'custom',
            },
            variant: 'primary',
            label: 'Settings',
            is_compact: true
          }
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
          }
        ]),
      }
    ),
    subnavigation: Object.assign(
      {},
      subnavigation.overview,
      {
        searchform: searchform
      }
    )
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
                variant: 'primary',
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

export const GroupActivity = () =>
  ActivityFullTemplate(
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
                variant: 'primary',
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
        subnavigation: subnavigation.activity,
        latest_members: Object.assign(
          {
            is_collapsible: true,
            extra_classes: 'wide-view'
          },
          members
        ),
        activity_stream: activity_stream
      },
      overview
    )
  );

export const GroupActivityModal = () =>
  ActivityFullTemplate(
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
                variant: 'primary',
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
        subnavigation: subnavigation.activity,
        latest_members: Object.assign(
          {
            is_collapsible: true,
            extra_classes: 'wide-view'
          },
          members
        ),
        activity_stream: Object.assign(
          {
            modal: {
              title: "Delete activity from activity stream",
              description: "Are you sure you want to delete this activity from the activity stream? Important: this action cannot be undone.",
              actions: [
                {
                  link: {
                    label: 'Yes, delete activity',
                    path: '?delete-comment=1',
                  },
                  extra_classes: 'ecl-link--button ecl-link--button-primary',
                },
                {
                  link: {
                    label: 'Cancel',
                    path: '?delete-comment=1',
                  },
                  extra_classes: 'ecl-link--button ecl-link--button-secondary',
                },
              ],
              call_to_action: {
                link: {
                  label: 'Close',
                }
              },
            },
          },
          activity_stream
        ),
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
        items: [
          ...mockItems(
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
          {
            content: MemberTemplate(
              Object.assign(
                {
                  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                },
                without(teaser.member, 'image')
              )
            ),
          },
        ],
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

export const GroupWikiPrivateWithContactBox = () =>
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
        contact_box: {
          title: 'Didn’t find what you were looking for?',
          body: 'Contact us at</br><a class="" href="mailto:support@eic.com">support@eic.com</a>',
          cta: {
            label: 'Contact us',
            link: 'mailto:support@eic.com'
          }
        }
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

export const GroupDocument = () =>
  DocumentFullTemplate(
    Object.assign(
      {
        sidebar_action: {
          extra_classes: 'ecl-collapsible-options--actions ecl-collapsible-options--full-width',
          collapse_label: 'Upload a file',
          icon_file_path: common.icon_file_path,
          items: [
            {
              link: {
                label: 'New File',
                path: '?path=new-file',
              }
            },
            {
              link: {
                label: 'New Image',
                path: '?path=new-image',
              }
            },
            {
              link: {
                label: 'New Video',
                path: '?path=new-video',
              }
            },
          ],
        },
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
                variant: 'primary',
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
        filters: filters.groupDiscussion,
        amount_options: teaserOverview.amount_options,
        active_filters: teaserOverview.active_filters,
        sort_options: teaserOverview.sort_options,
        sorting: sorting,
        subnavigation: subnavigation.document,
        items: mockItems(
          {
            content: FilelistTeaserTemplate(teaser.filelist),
          },
          4,
          () => ({
            content: FilelistTeaserTemplate(
              Object.assign(
                {},
                teaser.filelist,
                {
                  title: 'Filelist test lorem ipsum dolor',
                  highlight: {
                    is_active: true,
                    path: '#',
                    label: 'Highlight',
                    show_button: true
                  },
                  mime_type: 'doc',
                  files: ['file1.jpg']
                }
              )
            ),
          }),
          [0]
        ).concat(
          [
            { content: GalleryTeaserTemplate(teaser.gallery) },
            {
              content: GalleryTeaserTemplate(
                Object.assign(
                  {},
                  teaser.gallery,
                  {
                    images: [
                      { src: 'https://picsum.photos/320' }
                    ]
                  }
                )
              )
            },
            {
              content: GalleryTeaserTemplate(
                Object.assign(
                  {},
                  teaser.gallery,
                  {
                    images: [
                      { src: 'https://picsum.photos/320' },
                      { src: 'https://picsum.photos/320' },
                      { src: 'https://picsum.photos/320' },
                      { src: 'https://picsum.photos/320' }
                    ]
                  }
                )
              )
            },
            { content: VideoTeaserTemplate(teaser.video) },
            { content: VideoTeaserTemplate(teaser.video) },
          ]
        )
      },
      overview
    )
  );

export const GroupDocumentEmpty = () =>
  DocumentFullTemplate(
    Object.assign(
      {
        sidebar_action: {
          collapse_label: 'Upload a file',
          icon_file_path: common.icon_file_path,
          items: [
            {
              link: {
                label: 'New File',
                path: '?path=new-file',
              },
              icon: {
                name: 'document',
                type: 'custom',
              },
            },
            {
              link: {
                label: 'New Image',
                path: '?path=new-image',
              },
              icon: {
                name: 'media',
                type: 'custom',
              },
            },
            {
              link: {
                label: 'New Video',
                path: '?path=new-video',
              },
              icon: {
                name: 'play',
                type: 'custom',
              },
            },
          ],
        },
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
        filters: filters.groupDiscussion,
        amount_options: teaserOverview.amount_options,
        active_filters: teaserOverview.active_filters,
        sort_options: teaserOverview.sort_options,
        sorting: sorting,
        subnavigation: subnavigation.document,
        no_items_available_header: 'We haven’t found any search results',
        no_items_available: 'Please try again with another keyword.'
      },
      overview
    )
  );

let privateTeaser = {
  title: 'Incididunt minim cupidatat incididunt nulla tempor eiusmod ea sit enim.',
  title_after: '[open]',
  icon_file_path: common.icon_file_path,
  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
  type: {
    label: 'Private',
    extra_classes: 'ecl-tag--is-private',
  },
  image: {
    src: 'https://picsum.photos/320',
  },
  owner: {
    name: 'John Doe',
    path: '?owner=john-doe',
    image: {
      src: 'https://picsum.photos/160',
    },
  },
  timestamp: {
    label: 'Last activity 3 hours ago',
  },
  stats: [
    {
      label: 'Reactions',
      value: 287,
      icon: {
        type: 'custom',
        name: 'comment',
      },
    },
    {
      value: '120',
      label: 'Views',
      icon: {
        type: 'custom',
        name: 'views',
      },
    },
    {
      label: 'Documents',
      value: 8,
      icon: {
        type: 'custom',
        name: 'documents',
      },
    },
  ],
};
let restrictedTeaser = {
  title: 'Incididunt minim cupidatat incididunt nulla tempor eiusmod ea sit enim.',
  title_after: '[open]',
  icon_file_path: common.icon_file_path,
  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
  type: {
    label: 'Restricted',
    extra_classes: 'ecl-tag--is-restricted',
  },
  image: {
    src: 'https://picsum.photos/320',
  },
  owner: {
    name: 'John Doe',
    path: '?owner=john-doe',
    image: {
      src: 'https://picsum.photos/160',
    },
  },
  timestamp: {
    label: 'Last activity 3 hours ago',
  },
  stats: [
    {
      label: 'Reactions',
      value: 287,
      icon: {
        type: 'custom',
        name: 'comment',
      },
    },
    {
      value: '120',
      label: 'Views',
      icon: {
        type: 'custom',
        name: 'views',
      },
    },
    {
      label: 'Documents',
      value: 8,
      icon: {
        type: 'custom',
        name: 'documents',
      },
    },
  ],
};

export const OverviewPublic = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
    mainmenu: mainmenu,
    overview_header: {
      title: 'Groups',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.group,
    items: [
      ...mockItems(
        {
          content: TeaserTemplate(restrictedTeaser),
        },
        1,
        () => ({ content: TeaserTemplate(without(restrictedTeaser, 'image')) }),
        [0]
      ),
      ...mockItems(
        {
          content: TeaserTemplate(privateTeaser),
        },
        1,
        () => ({ content: TeaserTemplate(without(privateTeaser, 'image')) }),
        [0]
      ),
      ...mockItems(
        {
          content: TeaserTemplate(teaser.group),
        },
        8,
        () => ({ content: TeaserTemplate(without(teaser.group, 'image')) }),
        [0]
      )
    ],
    pagination: pagination,
  });

export const OverviewPrivate = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
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

let videoDetail = {
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
  contributors: contributors,
  comments: comments,
  social_share: socialShare,
  topics: topics,
  content: VideoTemplate(video),
};

export const VideoFullPublic = () => VideoFullTemplate(videoDetail);

export const VideoFullPrivate = () => VideoFullTemplate(
  Object.assign(
    {},
    videoDetail,
    {
      user: common.user,
    }
  )
)

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
  social_share: socialShare,
  topics: topics,
  content: FilelistTemplate(filelist),
};

export const FileListFullPublic = () => FileListFullTemplate(filelistDetail);

export const FileListFullPrivate = () => FileListFullTemplate(
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

export const GroupEventDetailed = () => GroupEventFullTemplate(
  Object.assign(
    {
      editorial_header: header,
      subnavigation: subnavigation.events,
      common: common,
      site_footer: siteFooter,
      site_header: siteHeader,
      searchform: searchform,
      breadcrumb: breadcrumb,
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
      contributors: contributors,
      comments: comments,
      social_share: socialShare,
      topics: topics,
      content: EventTemplate(event) + FilelistTemplate({
        ...filelist,
        title: "Event files",
        body: null
      }),
      event_infos: {
        title: 'Event details',
        action: {
          title: "Sign up to this event",
          text: "Get notified of the latest changes and take part in the discussion linked to this event.",
          action: {
            label: "Sign up now",
            url: "https://google.be"
          }
        },
        icon_file_path: common.icon_file_path,
        items: event_infos_items,
      },
      additional_sidebar_infos: {
        title: 'Organised by',
        content: 'Organiser 1, Organiser 2, Organiser 3'
      },
      action: {
        url: 'http;//google.be',
        label: 'Sign up'
      }
    },
    {
      user: common.user,
    }
  )
)
