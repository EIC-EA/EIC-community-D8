import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Explore our Groups',
  extra_classes: 'ecl-featured-content-collection--is-compact',
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
            extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
          },
          teaser.group
        )
      ),
    },
    3
  ),
};
