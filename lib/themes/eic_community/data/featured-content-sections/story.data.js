import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default {
  title: 'Latest news',
  content: TeaserOverviewTemplate({
    title: 'Latest news',
    items: mockItems(
      {
        content: StoryTeaserTemplate(teaser.story),
      },
      3
    ),
  }),
};
