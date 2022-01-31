import React from 'react';
import ResultItemWrapper from "../../../../ActivityStream/partials/ResultItemWrapper";

const svg = require('../../../../../../svg/svg')

const CommentStreamResultItem = (props) => {
  const fullname = `${props.result.ss_author_first_name} ${props.result.ss_author_last_name}`;

  return <ResultItemWrapper
    showDeleteModal={props.showDeleteModal}
    messageId={props.result.its_message_id}
    result={props.result}
    isAnonymous={props.isAnonymous}>

    <span dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon ecl-icon--s ecl-activity-stream__item__icon')}} />
    {!props.isAnonymous ? (
      <span
        className="ecl-activity-stream__item__description"
        dangerouslySetInnerHTML={{__html: `<a href="${props.result.ss_global_user_url}">${fullname}</a> ${props.translations.commented_on} <a href="${props.result.ss_path}">${props.result.ss_title}</a>`}}
      />
    ) : (
      <span
        className="ecl-activity-stream__item__description"
        dangerouslySetInnerHTML={{__html: `${fullname} ${props.translations.commented_on} <a href="${props.result.ss_path}">${props.result.ss_title}</a>`}}
      />
    )}
  </ResultItemWrapper>
}

export default CommentStreamResultItem;
