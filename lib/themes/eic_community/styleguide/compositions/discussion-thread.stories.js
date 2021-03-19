import docs from './discussion-thread.docs.mdx';

import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';

import common from '@theme/data/common.data';
import author from '@theme/data/author.data';

import { mockItems } from '@theme/snippets';
import discussionThread from '@theme/data/discussion-thread.data';

export const Base = () => DiscussionThreadTemplate(discussionThread);

export const Contributor = () =>
  DiscussionThreadTemplate(
    Object.assign(
      {
        members: mockItems(author, 99),
        from_contributor: true,
      },
      discussionThread
    )
  );

  export const Featured = () =>
  DiscussionThreadTemplate(
    Object.assign(
      {
        members: mockItems(author, 99),
        featured: {
          items: [
            {
              comment: 'Initial comment #1',
              comment_id: 'comment-001',
              author: author,
              timestamp: '7 days ago',
              stats: [
                {
                  label: 'Likes',
                  value: 20,
                  icon: {
                    type: 'custom',
                    name: 'like',
                  },
                },
              ],
            }
          ]
        }
      },
      discussionThread
    )
  );

export default {
  title: 'Compositions / Discussion Thread',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
