export default {
  title: 'Bundles / Group',
};

import teaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import overviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';

import { mockItems } from '@theme/snippets';

export const Teaser = () => teaserTemplate(teaser.group);

export const OverviewPublic = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
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
        content: teaserTemplate(teaser.group),
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
        content: teaserTemplate(teaser.group),
      },
      10
    ),
    pagination: pagination,
  });
