import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Explore our Groups',
  description:
    'Commodo ex laboris do velit reprehenderit voluptate in dolore reprehenderit aute voluptate eiusmod anim.',
  call_to_action: {
    link: {
      label: 'Discover all active groups',
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
          teaser.group
        )
      ),
    },
    3
  ),
};
