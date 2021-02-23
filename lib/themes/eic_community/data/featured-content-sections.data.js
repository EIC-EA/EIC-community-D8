import teaser from '@theme/data/teaser';

import WysiwygTemplate from '@theme/snippets/wysiwyg-example.html.twig';
import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import EventTeaserTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import MemberTeaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import StoryTeaserTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import { mockItems } from '@theme/snippets';

export default {
  items: [
    {
      title: 'Announcements',
      content: 'foo',
    },
    {
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
    },
    {
      title: 'Challenges & Sollutions',
      content: WysiwygTemplate(),
    },
    {
      title: 'Team',
      content: TeaserOverviewTemplate({
        title: 'Our Team',
        extra_classes: 'ecl-teaser-overview--has-columns ecl-teaser-overview--is-compact',
        items: mockItems(
          {
            content: MemberTeaserTemplate(
              Object.assign(
                {
                  extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
                },
                teaser.member
              )
            ),
          },
          3
        ),
      }),
    },
    {
      title: 'Events',
      content: TeaserOverviewTemplate({
        title: 'Events',
        items: mockItems(
          {
            content: EventTeaserTemplate(teaser.event),
          },
          3
        ),
      }),
    },
  ],
};
