import docs from './navigation-list.docs.mdx';

import NavigationListTermplate from '@theme/patterns/components/navigation-list/navigation-list.html.twig';

import common from '@theme/data/common.data';
import navigationList from '@theme/data/navigation-list.data';

export const Base = () =>
  NavigationListTermplate({
    icon_file_path: common.icon_file_path,
    items: navigationList,
  });

export const Collapsible = () =>
  NavigationListTermplate({
    icon_file_path: common.icon_file_path,
    items: navigationList,
    collapse: {
      label: 'Collapse',
    },
  });

export default {
  title: 'Components / Navigation List',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
