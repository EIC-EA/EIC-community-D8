import docs from './navigation-list.docs.mdx';

import NavigationListTermplate from '@theme/patterns/components/navigation-list/navigation-list.html.twig';

import common from '@theme/data/common.data';
import navigationList from '@theme/data/navigation-list.data';

export const Base = () =>
  NavigationListTermplate({
    icon_file_path: common.icon_file_path,
    items: navigationList.items,
  });

export const Collapsible = () =>
  NavigationListTermplate({
    icon_file_path: common.icon_file_path,
    items: navigationList.items,
    collapse: {
      label: 'Collapse',
    },
  });

export const WithTitle = () =>
  NavigationListTermplate({
    title: navigationList.title,
    title_element: 'h2',
    icon_file_path: common.icon_file_path,
    items: navigationList.items,
  });

export default {
  title: 'Components / Navigation List',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
