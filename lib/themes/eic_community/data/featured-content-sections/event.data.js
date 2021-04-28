import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import EventTeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default (title) => ({
  title: title,
  content: TeaserOverviewTemplate({
    title: title,
    items: mockItems(
      {
        content: EventTeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
            },
            teaser.event
          )
        ),
      },
      2,
      () => ({
        content: EventTeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
              highlight: {
                is_active: true,
              },
            },
            teaser.event
          )
        ),
      }),
      [0]
    ),
  }),
});
