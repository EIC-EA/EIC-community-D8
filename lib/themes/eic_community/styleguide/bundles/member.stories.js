import FullTemplate from '@theme/patterns/compositions/member/member.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/member/member.overview.html.twig';
import TeaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import memberInformationBlock from '@theme/data/member-information-block.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import sorting from '@theme/data/sorting.data';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';

import { mockItems } from '@theme/snippets';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  amount_options: teaserOverview.amount_options,
  active_filters: teaserOverview.active_filters,
  sort_options: teaserOverview.sort_options,
  site_footer: siteFooter,
  site_header: siteHeader,
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

export const Teaser = () => TeaserTemplate(teaser.member);

export const OverviewList = () =>
  OverviewTemplate(
    Object.assign(
      {
        items: mockItems(
          {
            content: TeaserTemplate(teaser.member),
          },
          12
        ),
      },
      overview
    )
  );

export const OverviewGallery = () =>
  OverviewTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-teaser-overview--has-columns',
        items: mockItems(
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
          12
        ),
      },
      overview
    )
  );

export const FullPublic = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
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
            },
            {
              label: 'Follow user',
              icon: {
                name: 'follow',
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
      },
      memberInformationBlock
    )
  );

export const FullPrivate = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
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
              link: {
                label: 'Activity feed',
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
      },
      memberInformationBlock
    )
  );

export default {
  title: 'Bundles / Member',
};
