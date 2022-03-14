import FullTemplate from '@theme/patterns/compositions/member/member.full.html.twig';
import DefaultTemplate from '@theme/patterns/compositions/member/member.default.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/member/member.overview.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import ButtonTemplate from '@ecl-twig/ec-component-button/ecl-button.html.twig'

import breadcrumb from '@theme/data/breadcrumb.activityFeed.data';
import common from '@theme/data/common.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import filters from '@theme/data/filters';
import mainmenu from '@theme/data/mainmenu.data';
import memberInformationBlock from '@theme/data/member-information-block.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import subnavigation from '@theme/data/subnavigation';
import sorting from '@theme/data/sorting.data';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topMenu from '@theme/data/top-menu.data';

import { mockItems } from '@theme/snippets';
import { without } from "../../snippets";

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  mainmenu: mainmenu,
  searchform: searchform,
  amount_options: teaserOverview.amount_options,
  active_filters: teaserOverview.active_filters,
  sort_options: teaserOverview.sort_options,
  site_footer: siteFooter,
  site_header: siteHeader,
  top_menu: topMenu,
  overview_header: {
    title: 'Members',
    image: {
      src: 'https://picsum.photos/1200/500',
    },
  },
  filter_title: 'Filter',
  filters: filters.member,
  sorting: sorting,
  pagination: pagination,
};
const teaserWithoutImage = TeaserTemplate(without(teaser.member, 'image'))
export const Teaser = () => TeaserTemplate(teaser.member);
export const TeaserUserNoImage = () => teaserWithoutImage;

export const OverviewList = () =>
  OverviewTemplate(
    Object.assign(
      {
        items: [
          ...mockItems(
            {
              content: TeaserTemplate(teaser.member),
            },
            12
          ),
          {
            content: teaserWithoutImage
          }
        ]
      },
      overview
    )
  );

export const OverviewGallery = () =>
  OverviewTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-teaser-overview--has-columns',
        items: [
          ...mockItems(
            {
              content: TeaserTemplate(
                Object.assign(
                  {
                    extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                  },
                  teaser.member
                )
              ),
            },
            12,
            () => ({
              content: TeaserTemplate(
                Object.assign(
                  {},
                  teaser.member,
                  {
                    location: {
                      label: 'Very long location name, Very long country'
                    },
                    description: 'Technical Service Line Director',
                    extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                  }
                )
              ),
            }),
            [1]
          ),
          {
            content: TeaserTemplate(
              Object.assign(
                {
                  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                },
                without(teaser.member, 'image')
              )
            ),
          },
        ]
      },
      overview
    )
  );

const commonInfos = {
  common: common,
  site_footer: siteFooter,
  site_header: siteHeader,
  searchform: searchform,
  editorial_header: {
    actions: [
      {
        link: {
          label: 'Direct message',
          path: '?path=direct-message',
        },
        icon: {
          name: 'send',
          type: 'custom',
        },
        flagged_groups: featuredContentSections.flaggedGroups('My groups'),
        flagged_projects: featuredContentSections.flaggedProjects('My projects'),
        flagged_interests: featuredContentSections.flaggedInterests('My interests'),
        flagged_events: featuredContentSections.flaggedEvents('My events'),
      },
      {
        content: ButtonTemplate({
          extra_classes: "ecl-link--button ecl-link--button-secondary",
          variant: 'secondary',
          icon_position: "before",
          label: 'Follow user',
          icon: {
            path: common.icon_file_path,
            name: 'follow',
            type: 'custom',
          },
        })

      },
    ],
    icon_file_path: common.icon_file_path,
    parent: {
      link: {
        label: 'All members',
        path: '?path=all-members',
      },
    },
  },
  flagged_groups: featuredContentSections.flaggedGroups('Groups'),
  flagged_projects: featuredContentSections.flaggedProjects('Projects'),
  flagged_interests: featuredContentSections.flaggedInterests('Interests'),
}

export const FullPublic = () =>
  FullTemplate(
    Object.assign(
      commonInfos,
      memberInformationBlock
    )
  );

export const FullPublicNoImage = () =>
  FullTemplate(
    Object.assign(
      commonInfos,
      without(memberInformationBlock, 'image')
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
        editorial_header: {
          actions: [
            {
              link: {
                label: 'Manage profile',
                path: '?path=direct-message',
              },
              icon: {
                name: 'gear',
                type: 'custom',
              },
            },
            {
              variant: "secondary",
              link: {
                label: 'My Activity feed',
                path: 'My activity feed',
              },
              icon: {
                name: 'feed',
                type: 'custom',
              },
            },
          ],
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'All members',
              path: '?path=all-members',
            },
          },
        },
        memberInformationBlock,
        flagged_groups: featuredContentSections.flaggedGroups('My groups'),
        flagged_projects: featuredContentSections.flaggedProjects('My projects'),
        flagged_interests: featuredContentSections.flaggedInterests('My interests'),
        flagged_events: featuredContentSections.flaggedEvents('My events'),
      },
      memberInformationBlock
    )
  );

export const MyActivity = () =>
  DefaultTemplate(
    {
      breadcrumb: breadcrumb,
      common: common,
      icon_file_path: common.icon_file_path,
      mainmenu: mainmenu,
      searchform: searchform,
      subnavigation: subnavigation.myActivity,
      amount_options: teaserOverview.amount_options,
      active_filters: teaserOverview.active_filters,
      sort_options: teaserOverview.sort_options,
      site_footer: siteFooter,
      site_header: siteHeader,
      top_menu: topMenu,
      overview_header: {
        title: 'My activity feed',
        extra_classes: 'ecl-overview-header--my-activity',
        icon_file_path: common.icon_file_path,
        actions: [
          {
            link: {
              label: 'Manage profile',
              path: '?path=direct-message',
            },
            icon: {
              name: 'gear',
              type: 'custom',
            },
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
        ],
      },
    }
  );

export default {
  title: 'Bundles / Member',
};
