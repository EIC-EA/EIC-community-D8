import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import GroupTeaserTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default (title) => ({
  title: title,
  content: TeaserOverviewTemplate({
    title: title,
    extra_classes: 'ecl-teaser-overview--has-compact-layout',
    items: mockItems(
      {
        content: GroupTeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--is-card ecl-teaser--as-grey',
            },
            teaser.project
          )
        ),
      },
      2
    ),
  }),
});
