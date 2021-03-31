import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Latest projects',
  description:
    'Sit aliquip commodo ut laboris adipisicing laborum reprehenderit veniam elit ad deserunt.',
  call_to_action: {
    link: {
      label: 'View all Projects',
    },
  },
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      content: TeaserTemplate(
        Object.assign(
          {
            extra_classes: 'ecl-teaser--as-card',
          },
          teaser.project
        )
      ),
    },
    3
  ),
};
