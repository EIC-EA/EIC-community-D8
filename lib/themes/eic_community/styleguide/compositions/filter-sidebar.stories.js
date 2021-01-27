import docs from './filter-sidebar.docs.mdx';

import filterSidebarTemplate from '@theme/patterns/compositions/filter-sidebar.html.twig';
import common from '@theme/data/common.data';
import filters from '@theme/data/filters.data';

export const Base = () =>
  filterSidebarTemplate({
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
