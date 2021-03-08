import docs from './filter-sidebar.docs.mdx';

import FilterSidebarTemplate from '@theme/patterns/compositions/filter-sidebar.html.twig';

import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';

export const Base = () =>
  FilterSidebarTemplate({
    icon_file_path: common.icon_file_path,
    title: 'Filter',
    items: filters.story,
  });

export default {
  title: 'Compositions / Filter Sidebar',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
