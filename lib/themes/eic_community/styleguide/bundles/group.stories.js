export default {
  title: 'Bundles / Group',
};

import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import FullTemplate from '@theme/patterns/compositions/group/group.full.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/group/group.overview.html.twig';
import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';

import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import discussionThread from '@theme/data/discussion-thread.data';
import featuredContentSections from '@theme/data/featured-content-sections';
import filters from '@theme/data/filters.data';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import sorting from '@theme/data/sorting.data';
import subnavigation from '@theme/data/subnavigation';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topics from '@theme/data/topics.data';
import topMenu from '@theme/data/top-menu.data';

import { editableField, mockItems } from '@theme/snippets';

const overview = {
  breadcrumb: breadcrumb,
  common: common,
  mainmenu: mainmenu,
  amount_options: teaserOverview.amount_options,
  active_filters: teaserOverview.active_filters,
  sort_options: teaserOverview.sort_options,
  site_footer: siteFooter,
  site_header: siteHeader,
  top_menu: topMenu,
  filter_title: 'Filter',
  filters: filters.member,
  sorting: sorting,
  pagination: pagination,
  subnavigation: subnavigation.discussion,
};

export const Teaser = () => TeaserTemplate(teaser.group);

export const FullPublic = () =>
  FullTemplate(
    Object.assign(
      {
        editorial_header: {
          icon_file_path: common.icon_file_path,
          description: editableField(),
          title: 'The big debate about the Climate',
          image: {
            src: 'http://picsum.photos/320/160',
          },
          tags: [
            {
              tag: {
                label: 'Public',
              },
              extra_classes: 'ecl-tag--is-public',
            },
          ],
          actions: [
            {
              label: '5',
              icon: {
                name: 'like',
                type: 'custom',
              },
            },
            {
              label: '24',
              icon: {
                name: 'follow',
                type: 'custom',
              },
            },
            {
              link: {
                label: 'Request to join the group',
                path: '#path=request',
              },
            },
          ],
          parent: {
            link: {
              label: 'All Groups',
              path: '?path=groups',
            },
          },
        },
        items: mockItems(
          {
            content: DiscussionThreadTemplate(discussionThread),
          },
          12,
          () => ({
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  from_contributor: true,
                },
                discussionThread
              )
            ),
          }),
          [1, 3]
        ),
      },
      overview
    )
  );

export const FullPrivate = () =>
  FullTemplate(
    Object.assign(
      {
        editorial_header: {
          icon_file_path: common.icon_file_path,
          description: editableField(),
          title: 'The big debate about the Climate',
          image: {
            src: 'http://picsum.photos/320/160',
          },
          tags: [
            {
              tag: {
                label: 'Private',
              },
              extra_classes: 'ecl-tag--is-private',
            },
          ],
          actions: [
            {
              label: '5',
              icon: {
                name: 'like',
                type: 'custom',
              },
            },
            {
              label: '24',
              icon: {
                name: 'follow',
                type: 'custom',
              },
            },
            {
              label: 'Post content',
              items: [
                {
                  label: 'Start Discussion',
                },
              ],
            },
          ],
          parent: {
            link: {
              label: 'All Groups',
              path: '?path=groups',
            },
          },
        },
        items: mockItems(
          {
            content: DiscussionThreadTemplate(discussionThread),
          },
          12,
          () => ({
            content: DiscussionThreadTemplate(
              Object.assign(
                {
                  from_contributor: true,
                },
                discussionThread
              )
            ),
          }),
          [1, 3]
        ),
      },
      overview
    )
  );

export const OverviewPublic = () =>
  OverviewTemplate({
    breadcrumb: breadcrumb,
    common: common,
    site_footer: siteFooter,
    site_header: siteHeader,
    mainmenu: mainmenu,
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
        content: TeaserTemplate(teaser.group),
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
        content: TeaserTemplate(teaser.group),
      },
      10
    ),
    pagination: pagination,
  });
