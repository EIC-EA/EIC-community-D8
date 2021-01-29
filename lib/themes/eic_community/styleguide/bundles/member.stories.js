export default {
  title: 'Bundles / Member',
};

import teaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import overviewTemplate from '@theme/patterns/compositions/member/member.overview.html.twig';

import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import teaser from '@theme/data/teaser.data';

export const Teaser = () => teaserTemplate(teaser.member);

export const Overview = () =>
  overviewTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    overview_header: {
      title: 'Stories',
    },
    filter_title: 'Filter',
    filters: filters.member,
    items: [
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
      {
        content: teaserTemplate(teaser.member),
      },
    ],
    pagination: pagination,
  });
