import docs from './inline-actions.docs.mdx';

import InlineStatsTemplate from '@theme/patterns/components/inline-stats.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  InlineStatsTemplate({
    icon_file_path: common.icon_file_path,
    items: [
      {
        label: 'Members',
        path: '#foo',
        value: 294,
        icon: {
          name: 'group',
          type: 'custom',
        },
        updates: {
          label: 'Latest members from the past 14 days.',
          value: 14,
        },
      },
      {
        label: 'Comments',
        path: '#foo',
        value: 33,
        icon: {
          name: 'feedback',
          type: 'general',
        },
      },
      {
        label: 'Attachments',
        value: 4,
        icon: {
          name: 'documents',
          type: 'custom',
        },
      },
      {
        label: 'events',
        value: 2,
        icon: {
          name: 'calendar',
          type: 'custom',
        },
      },
    ],
  });

export default {
  title: 'Components / Inline Stats',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
