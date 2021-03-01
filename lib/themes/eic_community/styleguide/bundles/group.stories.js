export default {
  title: 'Bundles / Group',
};

import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import mainmenu from '@theme/data/mainmenu.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';
import topMenu from '@theme/data/top-menu.data';

import { mockItems } from '@theme/snippets';

export const Teaser = () => TeaserTemplate(teaser.group);

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
    filters: filters.member,
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
    filters: filters.member,
    items: mockItems(
      {
        content: TeaserTemplate(teaser.group),
      },
      10
    ),
    pagination: pagination,
  });
