export default {
  title: 'Bundles / Event',
};

import TeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/event/event.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters';
import topMenu from '@theme/data/top-menu.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';

import { mockItems } from '@theme/snippets';

export const Teaser = () => TeaserTemplate(teaser.event);

export const TeaserCard = () => TeaserTemplate({...teaser.event, extra_classes: 'ecl-teaser--as-card'});


export const OverviewPublic = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    mainmenu: mainmenu,
    searchform: searchform,
    overview_header: {
      title: 'Events',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.member,
    items: mockItems(
      {
        content: TeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
            },
            teaser.event
          )
        ),
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
    searchform: searchform,
    overview_header: {
      title: 'Events',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
      actions: [
        {
          label: 'New Event',
          path: '?action=new-event',
        },
      ],
    },
    filter_title: 'Filter',
    filters: filters.member,
    items: mockItems(
      {
        content: TeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
            },
            teaser.event
          )
        ),
      },
      10
    ),
    pagination: pagination,
  });
