import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';

import { mockItems, without } from '@theme/snippets';

import comments from '@theme/data/comments.data';
import discussionThread from '@theme/data/discussion-thread.data';

export default {
  title: 'Discussions',
  items: mockItems(
    {
      content: DiscussionThreadTemplate(
        Object.assign(
          {
            extra_classes: 'ecl-discussion-thread--has-compact-layout',
            highlight: true,
            type: {
              icon: {
                name: 'question',
                type: 'custom',
              },
              label: 'Question',
            },
          },
          discussionThread
        )
      ),
    },
    4,
    () => ({
      content: DiscussionThreadTemplate(
        Object.assign(
          {
            highlight: {
              is_active: true,
            },
            extra_classes: 'ecl-discussion-thread--has-compact-layout',
            type: {
              icon: {
                name: 'idea',
                type: 'custom',
              },
              label: 'Idea',
            },
            featured: {
              items: [without(comments.items[0], 'items')],
            },
          },
          discussionThread
        )
      ),
    }),
    [0, 2]
  ),
};
