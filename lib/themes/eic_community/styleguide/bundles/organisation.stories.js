import teaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import overviewTemplate from '@theme/patterns/compositions/organisation/organisation.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import teaser from '@theme/data/teaser.data';

export const Teaser = () => teaserTemplate(teaser.organisation);

export const Overview = () =>
  overviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    overview_header: {
      title: 'Organisations',
      image: {
        src: 'https://picsum.photos/1200/500',
      },
    },
    filter_title: 'Filter',
    filters: filters.member,
    items: [
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
      {
        content: teaserTemplate(teaser.organisation),
      },
    ],
    pagination: pagination,
  });

export default {
  title: 'Bundles / Organisation',
};
