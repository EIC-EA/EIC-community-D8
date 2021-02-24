import teaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import privateTemplate from '@theme/patterns/compositions/organisation/organisation.private.html.twig';
import publicTemplate from '@theme/patterns/compositions/organisation/organisation.public.html.twig';
import overviewTemplate from '@theme/patterns/compositions/organisation/organisation.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';

import { editableField } from '@theme/snippets';
import organisationInformationBlock from '@theme/data/organisation-information-block.data';

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

export const FullPublic = () =>
  publicTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
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
  privateTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
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
