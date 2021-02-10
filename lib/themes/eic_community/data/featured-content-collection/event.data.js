import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Upcomming Events',
  call_to_action: {
    link: {
      label: 'View all Events',
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
          teaser.event
        )
      ),
    },
    3
  ),
};
