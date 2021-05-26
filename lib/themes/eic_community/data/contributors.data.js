import common from '@theme/data/common.data';

import { mockItems } from '@theme/snippets';

export default {
  title: 'Contributors',
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      description: 'Company XYZ',
      name: 'John Doe',
      path: '?path=am9obmRvZQ==',
      actions: [
        {
          label: 'Mail',
          path: 'mailto:info@example.com',
          icon: {
            type: 'custom',
            name: 'mail',
          },
        },
      ],
    },
    10
  ),
};
