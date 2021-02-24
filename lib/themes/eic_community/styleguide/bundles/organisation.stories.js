import TeaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import PrivateTemplate from '@theme/patterns/compositions/organisation/organisation.private.html.twig';
import PublicTemplate from '@theme/patterns/compositions/organisation/organisation.public.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/organisation/organisation.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';

import teaser from '@theme/data/teaser';

export const Teaser = () => TeaserTemplate(teaser.organisation);

export const Overview = () =>
  OverviewTemplate({
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
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
      {
        content: TeaserTemplate(teaser.organisation),
      },
    ],
    pagination: pagination,
  });

export const FullPublic = () =>
  PublicTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    featured_news: featuredContentSections.story,
  });

export const FullPrivate = () =>
  PrivateTemplate({
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    featured_news: featuredContentSections.story,
  });

export default {
  title: 'Bundles / Organisation',
};
