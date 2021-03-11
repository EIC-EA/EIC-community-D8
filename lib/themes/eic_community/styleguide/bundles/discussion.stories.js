import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/discussion/discussion.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import teaserOverview from '@theme/data/teaser-overview.data';
import sorting from '@theme/data/sorting.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import topMenu from '@theme/data/top-menu.data';
import teaser from '@theme/data/teaser';

import { mockItems } from '@theme/snippets';

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

export const Overview = () =>
  OverviewTemplate(
    Object.assign(
      {
        items: mockItems(
          {
            content: DiscussionThreadTemplate(),
          },
          12
        ),
      },
      overview
    )
  );

export default {
  title: 'Bundles / Discussion',
};
