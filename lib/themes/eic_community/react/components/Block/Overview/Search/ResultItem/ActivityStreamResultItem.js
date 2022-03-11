import React from 'react';
import ResultItemWrapper from '../../../ActivityStream/partials/ResultItemWrapper';
import svg from '../../../../../svg/svg';

class ActivityStreamResultItem extends React.Component {
  render() {
    const actionType = {
      comment: {
        svg: 'comment',
        label: this.props.translations.commented_on
      },
      node_comment: {
        svg: 'comment',
        label: this.props.translations.commented_on
      },
      node: {
        svg: 'comment',
        label: this.props.translations.commented_on
      },
      discussion: {
        svg:  this.props.result.ss_discussion_type,
        label: this.props.result.ss_operation_type
      },
      document: {
        svg:  'document',
        label: this.props.result.ss_operation_type
      },
      event: {
        svg:  'calendar',
        label: this.props.result.ss_operation_type
      },
      video: {
        svg:  this.props.result.ss_discussion_type,
        label: this.props.result.ss_operation_type
      },
      gallery: {
        svg:  'document',
        label: this.props.result.ss_operation_type
      },
      wiki_page: {
        svg:  'wiki',
        label: this.props.result.ss_operation_type
      },
    }
    const fullname = `${this.props.result.ss_author_first_name} ${this.props.result.ss_author_last_name}`;
    const message = `${this.props.result.tm_X3b_en_field_share_message && this.props.result.tm_X3b_en_field_share_message.length > 0 ? this.props.result.tm_X3b_en_field_share_message.shift() : ''}`

    if (!this.props.result || !this.props.result.ss_type) {
      return <></>;
    }

    return (
      <ResultItemWrapper
        showDeleteModal={this.props.showDeleteModal}
        messageId={this.props.result.its_message_id}
        result={this.props.result}
        isAnonymous={this.props.isAnonymous}
      >
        <span
          dangerouslySetInnerHTML={{
            __html: svg(actionType[this.props.result.ss_type].svg, 'ecl-icon ecl-icon--s ecl-activity-stream__item__icon'),
          }}
        />
        {!this.props.isAnonymous ? (
          <span
            className="ecl-activity-stream__item__description"
            dangerouslySetInnerHTML={{
              __html: `<a href="${this.props.result.ss_global_user_url}">${fullname}</a> ${actionType[this.props.result.ss_type].label} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`,
            }}
          />
        ) : (
          <span
            className="ecl-activity-stream__item__description"
            dangerouslySetInnerHTML={{
              __html: `${fullname} ${actionType[this.props.result.ss_type].label} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`,
            }}
          />
        )}

        {message.length > 0 && <p>{message}</p>}
      </ResultItemWrapper>
    );
  }
}

export default ActivityStreamResultItem;
