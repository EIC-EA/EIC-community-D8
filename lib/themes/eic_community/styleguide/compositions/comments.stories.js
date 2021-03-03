import docs from './comments.docs.mdx';

import CommentTemplate from '@theme/patterns/compositions/comment/comment.html.twig';
import ThreadTemplate from '@theme/patterns/compositions/comment/comment-thread.html.twig';
import OverviewTemplate from '@theme/patterns/compositions/comment/comment-overview.html.twig';

import comments from '@theme/data/comments.data';
import common from '@theme/data/common.data';

const item = comments.items[0];
const { items, ...props } = item;

export const Comment = () =>
  CommentTemplate(
    Object.assign(
      {
        icon_file_path: common.icon_file_path,
      },
      props
    )
  );

export const Thread = () =>
  ThreadTemplate(
    Object.assign(
      {
        icon_file_path: common.icon_file_path,
      },
      comments
    )
  );

export const OverviewPublic = () =>
  OverviewTemplate(
    Object.assign(
      {
        icon_file_path: common.icon_file_path,
      },
      comments
    )
  );

export const OverviewPrivate = () =>
  OverviewTemplate(
    Object.assign(
      {
        icon_file_path: common.icon_file_path,
        user: common.user,
      },
      comments
    )
  );

export default {
  title: 'Compositions / Comments',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
