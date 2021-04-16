import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Related stories',
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      content: TeaserTemplate(
        Object.assign(
          {
            extra_classes: 'ecl-teaser--has-compact-layout',
          },
          teaser.story
        )
      ),
    },
    2
  ),
};
