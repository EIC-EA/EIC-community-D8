import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import GroupTeaserTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default {
  title: 'My projects',
  content: TeaserOverviewTemplate({
    title: 'My projects',
    extra_classes: 'ecl-teaser-overview--is-compact',
    items: mockItems(
      {
        content: GroupTeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
            },
            teaser.project
          )
        ),
      },
      2
    ),
  }),
};
