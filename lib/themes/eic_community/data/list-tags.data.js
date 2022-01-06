import common from '@theme/data/common.data';

import { mockItems } from '@theme/snippets';

export default {
  title: 'Tags',
  icon_file_path: common.icon_file_path,
  is_collapsible: true,
  grid: true,
  collapse_label: 'Less',
  expand_label: 'More',
  items: mockItems(
    {
      tag: {
        type: "link",
        path: "/component-library/example",
        label: "Link tag"
      }
    },
    10
  ),
};
