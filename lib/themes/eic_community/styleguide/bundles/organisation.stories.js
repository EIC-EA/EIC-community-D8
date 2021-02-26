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

import { editableField } from '@theme/snippets';
import organisationInformationBlock from '@theme/data/organisation-information-block.data';

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
  PublicTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        featured_news: featuredContentSections.story,
        editorial_header: {
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'All organisations',
              path: '?path=all-organisations',
            },
          },
        },
      },
      organisationInformationBlock
    )
  );

export const FullPrivate = () =>
  PrivateTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        featured_news: featuredContentSections.story,
        editorial_header: {
          icon_file_path: common.icon_file_path,
          actions: [
            {
              link: {
                label: 'Edit Profile Company',
                path: '?path=my-activity-feed',
              },
              icon: {
                name: 'gear',
                type: 'custom',
              },
            },
            {
              link: {
                label: 'Add Content',
              },
              icon: {
                name: 'plus',
                type: 'ui',
              },
              items: [
                {
                  link: {
                    label: 'New Member',
                    path: 'foo',
                  },
                  icon: {
                    type: 'custom',
                    name: 'user_circle',
                  },
                },
                {
                  label: 'New Project',
                  icon: {
                    type: 'custom',
                    name: 'documents',
                  },
                },
              ],
            },
          ],
          parent: {
            link: {
              label: 'All organisations',
              path: '?path=all-organisations',
            },
          },
        },
      },
      organisationInformationBlock
    )
  );

export default {
  title: 'Bundles / Organisation',
};
