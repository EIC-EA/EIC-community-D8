import TeaserTemplate from '@theme/patterns/compositions/search/search.teaser.html.twig';
import SearchTemplate from '@theme/patterns/pages/search/search.html.twig';

import author from '@theme/data/author.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters';
import mainmenu from '@theme/data/mainmenu.data';
import pagination from '@theme/data/pagination.data';
import sorting from '@theme/data/sorting.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import teaser from '@theme/data/teaser';
import teaserOverview from '@theme/data/teaser-overview.data';
import topMenu from '@theme/data/top-menu.data';

import { mockItems, without, slice } from '@theme/snippets';

export const Search = () =>
  SearchTemplate({
    site_header: siteHeader,
    site_footer: siteFooter,
    title: 'Search',
    mainmenu_label: 'EIC Communities',
    mainmenu: mainmenu,
    filter_title: 'Refine your Search',
    filters: filters.search,
    amount_options: teaserOverview.amount_options,
    active_filters: teaserOverview.active_filters,
    sort_options: teaserOverview.sort_options,
    sorting: sorting,
    breadcrumb: breadcrumb,
    common: common,
    top_menu: topMenu,
    searchform: {
      icon_file_path: common.icon_file_path,
      tooltip: {
        label: 'Advanced Search',
        content: `
          <p>You can use these tips to focus your search.</p>
          <ul>
            <li>Search by combined wording by adding “speech marks” around several words;</li>
            <li>By adding AND between words, you result(s) will include multiple terms;</li>
            <li>By adding OR between words, your result(s) will include either or both terms;</li>
            <li>By adding NOT between words, your result(s) will include a term but not the other;</li>
            <li>By combining operators, your result(s) will follow the logic.</li>
          </ul>
          <p>Please note that operators are case sensitive and always</p>
        `,
      },
      field: {
        extra_attributes: [
          {
            name: 'placeholder',
            value: 'Insert keyword(s)',
          },
          {
            name: 'value',
            value: 'Cilmate',
          },
        ],
      },
      suggestion: '<p>Did you mean <a href="?s=climate">Climate</a></p>',
    },
    pagination: pagination,
    items: [
      {
        content: TeaserTemplate(teaser.search),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'story',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">story</span>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'discussion',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">discussion</span>',
                  parent_group: 'Found in group: <a href="#">Lorem ipsum dolor sit amet</a>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'document',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'uploaded a <span class="ecl-teaser__detail-type">document</span>',
                  parent_group: 'Found in group: <a href="#">Lorem ipsum dolor sit amet</a>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'wiki',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">wiki page</span>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'content-page',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">content page</span>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'calendar',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">event</span>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },
      {
        content: TeaserTemplate(
          Object.assign(
            {
              details: [
                {
                  icon: {
                    name: 'group',
                    type: 'custom',
                  },
                  contributor: author,
                  description: 'created a new <span class="ecl-teaser__detail-type">group</span>',
                  timestamp: {
                    label: '9 May 2021',
                  },
                },
              ],
            },
            without(teaser.search, 'details')
          )
        ),
      },

    ],
  });

export default {
  title: 'Pages / Search',
};
