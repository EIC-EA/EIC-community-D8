import { mockItems, without } from '@theme/snippets';

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
            members: {
              value: 33,
            },
          },
          teaser.event
        )
      ),
    },
    4,
    () => {
      return {
        content: TeaserTemplate(
          Object.assign(
            {
              is_highlighted: true,
              extra_classes: 'ecl-teaser--as-card',
              members: {
                value: 12,
              },
              type: {
                icon: {
                  name: 'map-marker',
                  type: 'custom',
                },
                label: 'Athens, Greece',
              },
            },
            without(teaser.event, 'type')
          )
        ),
      };
    },
    [2]
  ),
};
