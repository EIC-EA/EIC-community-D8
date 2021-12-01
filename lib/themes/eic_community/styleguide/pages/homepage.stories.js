import HomepagePrivateTemplate from '@theme/patterns/pages/homepage/homepage.private.html.twig';
import HomepagePublicTemplate from '@theme/patterns/pages/homepage/homepage.public.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import bulletinBlock from '../../data/bulletin-block.data';
import common from '@theme/data/common.data';
import factFigures from '@theme/data/fact-figures.data';
import featuredContentCollection from '@theme/data/featured-content-collection';
import featuredContentGrid from '@theme/data/featured-content-grid.data';
import mainmenu from '@theme/data/mainmenu.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import topMenu from '@theme/data/top-menu.data';

import { without, slice } from '@theme/snippets';

export const HomepagePublic = () =>
  HomepagePublicTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    mainmenu_label: 'EIC Communities',
    mainmenu: mainmenu,
    breadcrumb: breadcrumb,
    bulletin_block: Object.assign(
      {
        extra_classes: 'ecl-bulletin-block--as-list',
      },
      bulletinBlock
    ),
    common: common,
    hero: {
      insertion: 'Welcome to the EIC Community',
      title: 'Your community to find partners and share knowledge.',
      description: 'Ipsum in culpa sunt commodo quis quis sunt reprehenderit ipsum dolore minim.',
      image: 'https://picsum.photos/1600/450',
      actions: [
        {
          link: {
            label: 'Register',
            path: '?path=register',
            type: 'cta',
          },
        },
        {
          link: {
            label: 'Login',
            path: '?path=login',
            type: 'standalone',
          },
        },
      ],
    },
    usp: featuredContentCollection.card,
    featured_events: Object.assign(
      {
        items: slice(featuredContentCollection.event.items, 3),
      },
      without(featuredContentCollection.event, 'items')
    ),
    featured_news: featuredContentGrid,
    featured_groups: featuredContentCollection.group,
    fact_figures: factFigures,
    searchform: searchform,
    social_share: {
      title: 'Follow us',
      icon_file_path: common.icon_file_path,
      items: [
        {
          path: '?social-share=twitter',
          name: 'twitter',
          label: 'Twitter',
        },
        {
          path: '?social-share=facebook',
          name: 'facebook',
          label: 'Facebook',
          type: 'custom',
        },
        {
          path: '?social-share=linkedin',
          name: 'linkedin',
          label: 'LinkedIn',
        },
      ],
    },
    top_menu: topMenu,
  });

export const HomepagePrivate = () =>
  HomepagePrivateTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    breadcrumb: breadcrumb,
    mainmenu_label: 'EIC Communities',
    mainmenu: mainmenu,
    bulletin_block: Object.assign(
      {
        extra_classes: 'ecl-bulletin-block--as-list',
      },
      bulletinBlock
    ),
    common: common,
    usp: featuredContentCollection.card,
    editorial_header: {
      icon_file_path: common.icon_file_path,
      title: 'Welcome to the EIC Community',
      actions: [
        {
          link: {
            label: 'My activity feed',
            path: '?path=my-activity-feed',
          },
          icon: {
            name: 'user',
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
                label: 'New Story',
                path: 'foo',
              },
              icon: {
                type: 'custom',
                name: 'news',
              },
            },
            {
              label: 'New event',
              icon: {
                type: 'custom',
                name: 'calendar',
              },
            },
          ],
        },
      ],
    },
    fact_figures: Object.assign(
      {
        items: slice(factFigures.items, 4),
      },
      without(factFigures, 'items')
    ),
    featured_events: Object.assign(
      {
        items: slice(featuredContentCollection.event.items, 3),
      },
      without(featuredContentCollection.event, 'items')
    ),
    featured_news: Object.assign(
      {
        items: slice(featuredContentGrid.items, 3),
      },
      without(featuredContentGrid, 'items')
    ),
    featured_groups: featuredContentCollection.group,
    featured_projects: featuredContentCollection.project,
    searchform: searchform,
    social_share: {
      title: 'Follow us',
      icon_file_path: common.icon_file_path,
      items: [
        {
          path: '?social-share=twitter',
          name: 'twitter',
          label: 'Twitter',
        },
        {
          path: '?social-share=facebook',
          name: 'facebook',
          label: 'Facebook',
          type: 'custom',
        },
        {
          path: '?social-share=linkedin',
          name: 'linkedin',
          label: 'LinkedIn',
        },
      ],
    },
    user: common.user,
    top_menu: topMenu,
  });

export default {
  title: 'Bundles / Homepage',
};
