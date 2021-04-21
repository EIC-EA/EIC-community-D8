import teaser from '@theme/data/teaser';

import WysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';
import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import EventTeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import MemberTeaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import { editableField, mockItems } from '@theme/snippets';

export default {
  items: [
    {
      title: 'Introduction',
      content: editableField(),
      id: '001',
      updates: {
        label: 'Unread news',
        value: 2,
      },
    },
    {
      title: 'About',
      id: '002',
      content: WysiwygTemplate(),
    },
    {
      title: 'How to apply',
      id: '003',
      content: WysiwygTemplate(),
    },
  ],
};
