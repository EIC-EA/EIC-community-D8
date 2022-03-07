import docs from './notifications-management-line.docs.mdx';

import NotifManagementTemplate from '@theme/patterns/compositions/notifications-management.html.twig';
import common from '@theme/data/common.data';

export const base = () =>
  NotifManagementTemplate({
    title: 'Topics',
    icon_file_path: common.icon_file_path,
    items:[
      {
        name: {
          label: 'Topic 01',
          path: '#'
        },
        state: true,
        items:[
          {
            name: {
              label: 'Topic 01: subtopic 01',
              path: '#'
            },
            state: false
          },
          {
            name: {
              label: 'Topic 01: subtopic 02',
              path: '#'
            },
            state: true
          }
        ]
      },
      {
        name: {
          label: 'Topic 02',
          path: '#'
        },
        state: false,
      }
    ]
  });

export default {
  title: 'Compositions / Notifications management line',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
