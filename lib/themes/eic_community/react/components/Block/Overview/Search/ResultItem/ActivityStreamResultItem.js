import React from 'react';
import ResultItemWrapper from '../../../ActivityStream/partials/ResultItemWrapper';
import svg from '../../../../../svg/svg';
import axios from "axios";

class ActivityStreamResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
    };
    this.getStatistics = this.getStatistics.bind(this);
  }

  componentDidMount() {
    this.setState({stats: this.getStatistics()});
  }

  getStatistics() {
    const endpoint = window.drupalSettings?.node_statistics_url;
    if (!endpoint) {
      return null;
    }

    let self = this;
    axios.get(endpoint, {
      params: {
        bundle: this.props.result.ss_type.replace(' ', '_').toLowerCase(),
        entityId: this.props.result.its_message_node_ref_id,
      },
      withCredentials: true,
    }).then(response => {
      let statistics = response.data
      const mapping = {
        downloads: 'its_document_download_total',
        comments: 'its_content_comment_count',
        like_content: 'its_flag_like_content',
        views: 'its_statistics_view'
      }

      Object.keys(statistics).forEach((key) => self.props.result[mapping[key]] = statistics[key])
      self.setState({loading: false})
    });

  }

  render() {
    const defaultLabel = this.props.result.ss_operation_type === 'created'
      ? `${this.props.result.ss_operation_type} a new ${this.props.result.ss_global_content_type}`
      : `${this.props.result.ss_operation_type} a ${this.props.result.ss_global_content_type}`;

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
        svg: this.props.result.ss_discussion_type,
        label: defaultLabel
      },
      document: {
        svg: 'document',
        label: defaultLabel
      },
      event: {
        svg: 'calendar',
        label: defaultLabel
      },
      video: {
        svg: this.props.result.ss_discussion_type,
        label: defaultLabel
      },
      gallery: {
        svg: 'document',
        label: defaultLabel
      },
      wiki_page: {
        svg: 'wiki',
        label: defaultLabel
      },
    }
    const fullname = `${this.props.result.ss_author_first_name} ${this.props.result.ss_author_last_name}`;
    const message = `${this.props.result.tm_X3b_en_field_share_message && this.props.result.tm_X3b_en_field_share_message.length > 0 ? this.props.result.tm_X3b_en_field_share_message.shift() : ''}`;

    if (!this.props.result || !this.props.result.ss_type || this.state.loading) {
      return <></>;
    }

    const entity_bundle = this.props.result.ss_type.replace(' ', '_').toLowerCase();

    return (<ResultItemWrapper
        showDeleteModal={this.props.showDeleteModal}
        messageId={this.props.result.its_message_id}
        result={this.props.result}
        isAnonymous={this.props.isAnonymous}
      >
        {actionType.hasOwnProperty(entity_bundle) &&
          <span
            dangerouslySetInnerHTML={{
              __html: svg(actionType[entity_bundle].svg, 'ecl-icon ecl-icon--s ecl-activity-stream__item__icon'),
            }}
          />
        }
        { actionType.hasOwnProperty(entity_bundle) && !this.props.isAnonymous ? (
          <span
            className="ecl-activity-stream__item__description"
            dangerouslySetInnerHTML={{
              __html: `<a href="${this.props.result.ss_global_user_url}">${fullname}</a> ${actionType[entity_bundle].label} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`,
            }}
          />
          ) : (
            <span
              className="ecl-activity-stream__item__description"
              dangerouslySetInnerHTML={{
                __html: `${fullname} ${actionType[entity_bundle].label} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`,
              }}
            />
          )
        }

        {message.length > 0 && <p>{message}</p>}
      </ResultItemWrapper>
    );
  }
}

export default ActivityStreamResultItem;
