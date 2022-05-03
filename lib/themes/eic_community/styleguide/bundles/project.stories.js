import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/project/project.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';

export const Teaser = () => TeaserTemplate(teaser.project);

export const Overview = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    mainmenu: mainmenu,
    site_footer: siteFooter,
    site_header: siteHeader,
    searchform: searchform,
    overview_header: {
      title: 'Projects',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.member,
    items: mockItems(
      {
        content: TeaserTemplate(teaser.project),
      },
      10
    ),
    pagination: pagination,
  });

export default {
  title: 'Bundles / Project',
};
