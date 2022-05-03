import React from "react";
import ReactDOM from "react-dom";
import CommentsDiscussion from "./index";
import ReactNotification from 'react-notifications-component';
import "react-notifications-component/dist/theme.css";

const element = document.getElementById('comments-discussion');

ReactDOM.render(
  <>
    <ReactNotification />
    <CommentsDiscussion
        discussionId={element.dataset.discussionId}
        highlightedCommentId={element.dataset.highlightedComment}
    />
  </>,
  element
);
