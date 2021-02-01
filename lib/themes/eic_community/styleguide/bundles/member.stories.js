import teaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import overviewTemplate from '@theme/patterns/compositions/member/member.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import sorting from '@theme/data/sorting.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import teaser from '@theme/data/teaser.data';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
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
  items: [
    {
      content: teaserTemplate(
        Object.assign(
          {
            type: {
              label: 'Top contributor',
              icon: {
                name: 'star_circle',
                type: 'custom',
              },
            },
          },
          teaser.member
        )
      ),
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
};

export const Teaser = () => teaserTemplate(teaser.member);

export const OverviewList = () => overviewTemplate(overview);

export const OverviewGallery = () =>
  overviewTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-teaser-overview--has-columns',
      },
      overview
    )
  );

export default {
  title: 'Bundles / Member',
};
