import TeaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import FullTemplate from '@theme/patterns/compositions/organisation/organisation.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/organisation/organisation.overview.html.twig';
import OrganisationDetailsTemplate from '@theme/patterns/compositions/organisation/organisation.details.html.twig';
import AnnouncementTemplate from '@theme/patterns/components/announcements_item.html.twig';
import StoryTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import TTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';



import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import featuredContentCollection from '@theme/data/featured-content-collection';
import filters from '@theme/data/filters';
import ListTagsData from '@theme/data/list-tags.data.js';
import mainmenu from '@theme/data/mainmenu.data';
import organisationInformationBlock from '@theme/data/contact-information-block.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import subnavigation from '@theme/data/subnavigation';
import teaser from '@theme/data/teaser';
import topMenu from '@theme/data/top-menu.data';


import { editableField, mockItems, without } from '@theme/snippets';


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
    {
      icon: {
        name: 'share',
        type: 'general',
      },
      link: {
        label: 'Share',
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
      label: 'Post Content',
      items: [
        {
          link: {
            label: 'label 1',
          },
        },
        {
          link: {
            label: 'label 2',
          },
        },
      ],
    },
    {
      link: {
        label: 'Manage content',
        path: '#path=request',
      },
      icon: {
        name: 'gear',
        type: 'custom',
      },
    },
  ],
  parent: {
    link: {
      label: 'All Organisation',
      path: '?path=groups',
    },
  },
};

export const Teaser = () => TeaserTemplate(teaser.organisation);

export const Overview = () => OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    top_menu: topMenu,
    searchform: searchform,
    overview_header: {
      title: 'Organisations',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.member,
    my_items: mockItems(
      {
      content: TeaserTemplate({
        ...teaser.organisation,
        extra_classes: 'ecl-teaser--as-card-white ecl-teaser--is-card'
      })
    }, 3),
    items: mockItems(
      {content: TeaserTemplate({
          ...teaser.organisation,
          extra_classes: 'ecl-teaser--grey'
        })},
      5
    ),
    pagination: pagination,
    mainmenu: mainmenu,
    extra_classes: 'ecl-teaser-overview--has-columns',
  });

export const Detail = () => OrganisationDetailsTemplate(
  Object.assign(
    {
      editorial_header: header,
      subnavigation: subnavigation.organisation,
      common: common,
      site_footer: siteFooter,
      site_header: siteHeader,
      searchform: searchform,
      breadcrumb: breadcrumb,
      details: {
        title: 'Organisation details',
        items: [
          {
            title: 'Organisation',
            type: 'stats',
            items: [
              {
                name: 'Employees',
                value: '300'
              },
              {
                name: 'Active members',
                value: '50'
              },
              {
                name: 'Annual turnover',
                value: '€ 999.999.999.999,00'
              },
              {
                name: 'Date of establishement',
                value: '2020'
              }
            ]
          },
          {
            ...ListTagsData,
            title: 'Services and products offered',
            type: 'tags'
          },
          {
            ...ListTagsData,
            title: 'Target markets',
            type: 'tags'
          },
          {
            ...ListTagsData,
            title: 'Topics',
            type: 'tags'
          }
        ]
      },
      icon_file_path: common.icon_file_path,
      announcements: {
        title: 'Announcements',
        items: [
          {
            title: 'We are looking for',
            extra_classes: 'ecl-featured-list--is-organisation-announcements',
            title_element: 'h4',
            icon_file_path: common.icon_file_path,
            is_collapsible: true,
            collapse_label: 'Show 2 more',
            items: mockItems({
              content: AnnouncementTemplate({
                title: 'Looking for annoucement title',
                description: editableField(),
                cta: {
                  link: 'mailto:example@easme.be',
                  label: 'Contact us'
                }
              })
            }, 4)
          },
          {
            title: 'What we offer',
            extra_classes: 'ecl-featured-list--is-organisation-announcements',
            title_element: 'h4',
            icon_file_path: common.icon_file_path,
            is_collapsible: true,
            collapse_label: 'Show 2 more',
            items: mockItems({
              content: AnnouncementTemplate({
                title: 'What we offering annoucement title',
                description: editableField(),
                cta: {
                  link: 'mailto:example@easme.be',
                  label: 'Contact us'
                }
              })
            }, 4)
          }
        ]
      },
      contact: {
        link: {
          title: 'Link',
          items: [
            {
              icon: {
                name: 'link',
                type: 'custom',
              },
              link: {
                label: 'Visit website',
                path: 'http://google.com'
              },
            }
          ]
        },
        social: {
          title: 'Social media',
          icon_file_path: common.icon_file_path,
          items: [
            {
              path: '?social-share=twitter',
              name: 'twitter',
              label: 'Twitter',
            },
            {
              path: '?social-share=facebook',
              name: 'facebook-current',
              label: 'Facebook',
              type: 'custom',
            },
            {
              path: '?social-share=linkedin',
              name: 'linkedin',
              label: 'LinkedIn',
            }
          ],
        },
        data: {
          title: 'Contact',
          items: [
            {
              name: 'A-M Steinmann Delaware',
              link: {
                icon: {
                  name: 'mail',
                  type: 'custom',
                },
                link: {
                  label: 'Send email',
                  path: 'mailto:test@example.com'
                },
              }
            }
          ]
        },
        adresses: {
          title: 'Adresses',
          items: [
            {
              title: 'Address 1',
              description: '16 W Solcito Lane<br>Third door on the left<br>Phoenix, AZ, 85013<br>United States'
            },
            {
              title: 'Address 2',
              description: '16 W Solcito Lane<br>Third door on the left<br>Phoenix, AZ, 85013<br>United States'
            }
          ]
        },
        locations: {
          title: 'Locations',
          collapse_label: '2 locations',
          items: [
            {
              title: 'Location 1',
              description: '16 W Solcito Lane<br>Third door on the left<br>Phoenix, AZ, 85013<br>United States'
            },
            {
              title: 'Location 2',
              description: '16 W Solcito Lane<br>Third door on the left<br>Phoenix, AZ, 85013<br>United States'
            }
          ]
        }
      },
      news: Object.assign(featuredContentCollection.story, {
        call_to_action: {
          link: {
            label: 'See all news',
          },
        },
        items: mockItems(
          {
            content: StoryTemplate(teaser.story),
          },
          2
        ),
      }),
      teams: {
        title: 'Our team'
      },
      events: {
        title: 'Events attending'
      }
      // editorial_actions: {
      //   icon_file_path: common.icon_file_path,
      //   items: [
      //     {
      //       link: {
      //         label: 'Bookmarked',
      //         path: '?path=bookmark',
      //       },
      //       icon: {
      //         type: 'custom',
      //         name: 'tag',
      //       },
      //     },
      //     {
      //       link: {
      //         label: 'Liked (27)',
      //         path: '?path=like',
      //       },
      //       icon: {
      //         type: 'custom',
      //         name: 'like',
      //       },
      //     },
      //     {
      //       link: {
      //         label: 'Add to highlight',
      //         path: '?path=login',
      //       },
      //       icon: {
      //         type: 'custom',
      //         name: 'star_circle',
      //       },
      //     }
      //   ],
      // },
      // contributors: contributors,
      // comments: comments,
      // social_share: socialShare,
      // topics: topics,
      // content: EventTemplate(event) + FilelistTemplate({
      //   ...filelist,
      //   title: "Event files",
      //   body: null
      // }),
      // event_infos: {
      //   title: 'Event details',
      //   action: {
      //     title: "Sign up to this event",
      //     text: "Get notified of the latest changes and take part in the discussion linked to this event.",
      //     action: {
      //       label: "Sign up now",
      //       url: "https://google.be"
      //     }
      //   },
      //   icon_file_path: common.icon_file_path,
      //   items: event_infos_items,
      // },
      // additional_sidebar_infos: {
      //   title: 'Organised by',
      //   content: 'Organiser 1, Organiser 2, Organiser 3'
      // },
      // action:{
      //   url: 'http;//google.be',
      //   label: 'Sign up'
      // }
    }
)
)

export const FullPublic = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        searchform: searchform,
        top_menu: topMenu,
        featured_events: featuredContentSections.event('Available Events'),
        featured_news: featuredContentSections.story('Latest news'),
        featured_team: featuredContentSections.member('Our Team'),
        editorial_header: {
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'All organisations',
              path: '?path=all-organisations',
            },
          },
        },
        mainmenu: mainmenu,
      },
      organisationInformationBlock
    )
  );

export const FullPrivate = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        searchform: searchform,
        featured_events: featuredContentSections.event('Events attending'),
        featured_news: featuredContentSections.story('Latest news'),
        editorial_header: {
          icon_file_path: common.icon_file_path,
          actions: [
            {
              link: {
                label: 'Edit Profile Company',
                path: '?path=my-activity-feed',
              },
              icon: {
                name: 'gear',
                type: 'custom',
              },
            },
            {
              link: {
                label: 'Add Content',
              },
              icon: {
                name: 'plus',
                type: 'ui',
              },
              items: [
                {
                  link: {
                    label: 'New Member',
                    path: 'foo',
                  },
                  icon: {
                    type: 'custom',
                    name: 'user_circle',
                  },
                },
                {
                  label: 'New Project',
                  icon: {
                    type: 'custom',
                    name: 'documents',
                  },
                },
              ],
            },
          ],
          parent: {
            link: {
              label: 'All organisations',
              path: '?path=all-organisations',
            },
          },
        },
        mainmenu: mainmenu,
        user: common.user,
      },
      organisationInformationBlock
    )
  );

export default {
  title: 'Bundles / Organisation',
};
