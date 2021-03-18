import docs from './discussion-thread.docs.mdx';

import DiscussionThreadTemplate from '@theme/patterns/compositions/discussion-thread.html.twig';

import common from '@theme/data/common.data';
import author from '@theme/data/author.data';

import { mockItems } from '@theme/snippets';
import discussionThread from '@theme/data/discussion-thread.data';

export const Base = () => DiscussionThreadTemplate(discussionThread);

export const Highlighted = () =>
  DiscussionThreadTemplate(
    Object.assign(
      {
        members: mockItems(author, 99),
        is_highlighted: true,
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
