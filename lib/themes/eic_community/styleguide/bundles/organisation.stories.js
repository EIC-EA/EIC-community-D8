import TeaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import FullTemplate from '@theme/patterns/compositions/organisation/organisation.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/organisation/organisation.overview.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import filters from '@theme/data/filters';
import mainmenu from '@theme/data/mainmenu.data';
import organisationInformationBlock from '@theme/data/contact-information-block.data';
import pagination from '@theme/data/pagination.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';
import topMenu from '@theme/data/top-menu.data';

import { editableField } from '@theme/snippets';

export const Teaser = () => TeaserTemplate(teaser.organisation);

export const Overview = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    top_menu: topMenu,
    mainmenu: mainmenu,
    searchform: searchform,
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
    mainmenu: mainmenu,
  });

export const FullPublic = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        searchform: searchform,
        top_menu: topMenu,
        featured_events: featuredContentSections.event('Available Events'),
        featured_news: featuredContentSections.story('Latest news'),
        featured_team: featuredContentSections.member('Our Team'),
        editorial_header: {
          icon_file_path: common.icon_file_path,
          parent: {
            link: {
              label: 'All organisations',
              path: '?path=all-organisations',
            },
          },
        },
        mainmenu: mainmenu,
      },
      organisationInformationBlock
    )
  );

export const FullPrivate = () =>
  FullTemplate(
    Object.assign(
      {
        common: common,
        site_footer: siteFooter,
        site_header: siteHeader,
        searchform: searchform,
        featured_events: featuredContentSections.event('Events attending'),
        featured_news: featuredContentSections.story('Latest news'),
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
        mainmenu: mainmenu,
        user: common.user,
      },
      organisationInformationBlock
    )
  );

export default {
  title: 'Bundles / Organisation',
};
