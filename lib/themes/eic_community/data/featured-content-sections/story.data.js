import { mockItems } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import teaser from '@theme/data/teaser';

export default (title) => ({
  title: title,
  content: TeaserOverviewTemplate({
    title: title,
    items: mockItems(
      {
        content: StoryTeaserTemplate(teaser.story),
      },
      3
    ),
  }),
});
