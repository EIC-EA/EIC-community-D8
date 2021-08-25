import React from 'react';

const svg = require('../../../../../../svg/svg')
import ResultItemWrapper from "../../../../ActivityStream/partials/ResultItemWrapper";

class CommentStreamResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const fullname = `${this.props.result.ss_author_first_name} ${this.props.result.ss_author_last_name}`;

    return <ResultItemWrapper showDeleteModal={this.props.showDeleteModal} messageId={this.props.result.its_message_id} result={this.props.result}>

            <span dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon ecl-icon--s ecl-activity-stream__item__icon')}} />
            <span
              className="ecl-activity-stream__item__description"
              dangerouslySetInnerHTML={{__html: `<a href="${this.props.result.ss_global_user_url}">${fullname}</a> ${this.props.translations.commented_on} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`}}
          >
          </span>
    </ResultItemWrapper>
  }
}

export default CommentStreamResultItem;
