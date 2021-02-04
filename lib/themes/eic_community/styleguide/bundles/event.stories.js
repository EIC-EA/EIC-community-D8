export default {
  title: 'Bundles / Event',
};

import teaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import overviewTemplate from '@theme/patterns/compositions/event/event.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';

import { mockItems } from '@theme/snippets';

export const Teaser = () => teaserTemplate(teaser.event);

export const OverviewPublic = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
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
        content: teaserTemplate(teaser.event),
      },
      10
    ),
    pagination: pagination,
  });

export const OverviewPrivate = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
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
        content: teaserTemplate(teaser.event),
      },
      10
    ),
    pagination: pagination,
  });
