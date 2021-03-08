import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import GroupTeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default {
  title: 'My groups',
  content: TeaserOverviewTemplate({
    extra_classes: 'ecl-teaser-overview--has-columns ecl-teaser-overview--is-compact',
    title: 'My groups',
    items: mockItems(
      {
        content: GroupTeaserTemplate(teaser.group),
      },
      2
    ),
  }),
};
