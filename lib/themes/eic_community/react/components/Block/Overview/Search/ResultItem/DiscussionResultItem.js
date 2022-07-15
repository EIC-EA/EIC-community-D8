import React from 'react';
import HighlightLink from "./Library/Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../Services/TimeHelper";
import Comment from "./Discussion/Comment";
import UserImage from "../../../../Utils/UserImage";
import {url} from "../../../../../Services/UrlHelper"

const svg = require('../../../../../svg/svg')

class DiscussionResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content,
      comment: {
        text: this.props.result.ss_discussion_last_comment_text,
        author: this.props.result.ss_discussion_last_comment_author,
        author_url: this.props.result.ss_discussion_last_comment_url,
        author_image: this.props.result.ss_discussion_last_comment_author_image,
        time: this.props.result.ss_discussion_last_comment_timestamp,
      }
    };

    this.updateHighlight = this.updateHighlight.bind(this);
  }

  updateHighlight(isHighlighted) {
    this.setState({
      isHighlighted,
    })
  }

  render() {
    const fullname = `${this.props.result.ss_content_first_name} ${this.props.result.ss_content_last_name}`;
    let isHighlighted = this.state.isHighlighted;
    isHighlighted = typeof isHighlighted !== 'undefined' && isHighlighted !== false;
    const topics = this.props.result.sm_content_field_vocab_topics_string;
    const itemUrl = url(this.props.result.ss_url)

    return (<div className="ecl-teaser-overview__item ">
        <div className={`ecl-discussion-thread ${isHighlighted && 'ecl-discussion-thread--is-highlighted'}`}>
          <div className="ecl-discussion-thread__main">
            <div className="ecl-discussion-thread__image-wrapper">
              <div
                className="ecl-author ecl-author--is-medium  ecl-author--is-minimal ecl-discussion-thread__author-image">
                <div className="ecl-author__information">
                <span className="ecl-author__label ">

                  </span>
                </div>
                <div className="ecl-author__aside">
                  <UserImage
                    className={'ecl-author__media-wrapper'}
                    figureClassName={'ecl-author__media'}
                    imgClassName={'ecl-media-container__media'}
                    figureEmptyClassName={'ecl-author__media ecl-author__media--empty'}
                    src={!this.props.isAnonymous ? url(this.props.result.ss_content_author_formatted_image) : null}
                    alt={fullname}
                    url={itemUrl}
                  />
                </div>
              </div>
            </div>
            <div className="ecl-discussion-thread__content">
              <div className="ecl-discussion-thread__toolbar">
                <div className="ecl-discussion-thread__type">
                  <span
                    dangerouslySetInnerHTML={{__html: svg(this.props.result.ss_content_field_discussion_type, 'ecl-icon ecl-icon--s ecl-discussion-thread__type-icon')}}
                  />
                  <span className="ecl-discussion-thread__type-label">{this.props.result.ss_content_field_discussion_type}</span>
                </div>
                <HighlightLink
                  updateHighlight={this.updateHighlight}
                  isHighlighted={isHighlighted}
                  currentGroupId={this.props.result.its_global_group_parent_id}
                  nodeId={this.props.result.its_content_nid}
                  translation={this.props.translations}
                />
              </div>
              <div className="ecl-discussion-thread__header">
                <div className="ecl-discussion-thread__tags">
                  <div className="ecl-discussion-thread__tags-items">
                    {undefined !== topics && topics.length > 0 && topics.map((topic) => {
                      return (<div key={topic} className="ecl-discussion-thread__tags-item">
                        <span className="ecl-tag ecl-discussion-thread__tag">{topic}</span>
                      </div>)
                    })}
                  </div>
                </div>
              </div>

              <h3 className="ecl-discussion-thread__title">
                <a href={itemUrl} className="ecl-link ecl-link--standalone">{this.props.result.tm_global_title}</a></h3>
              <p className="ecl-discussion-thread__description">{this.props.result.ss_global_body_no_html && this.props.result.ss_global_body_no_html.replaceAll('/n', '')}</p>
            </div>
          </div>
          <div className="ecl-discussion-thread__footer">
            <div className="ecl-discussion-thread__meta">
              <div class="ecl-discussion-thread__author">
                <span className="ecl-discussion-thread__started-by-label">Started by</span>&nbsp;
                {!this.props.isAnonymous ? (
                  <a href={this.props.result.ss_global_user_url} className="ecl-link ecl-link--standalone ecl-discussion-thread__author">{fullname}</a>
                ) : (
                  <span className="ecl-author__label">{fullname}</span>
                )}
              </div>
              <div className="ecl-timestamp ecl-discussion-thread__timestamp ecl-timestamp--meta">
                <span
                  dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                />
                <time className="ecl-timestamp__label">{timeDifferenceFromNow(Date.parse(this.props.result.ds_content_created) / 1000)}</time>
              </div>

              <div className="ecl-discussion-thread__stats">
                <div className="ecl-discussion-thread__stats-items">
                  <div className="ecl-discussion-thread__stats-item">
                    <span
                      dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon ecl-icon--xs ecl-discussion-thread__stats-item-icon')}}
                    />
                    <span className="ecl-discussion-thread__stats-item-label">Reactions</span>
                    <span className="ecl-discussion-thread__stats-item-value">{this.props.result.its_flag_like_content || 0}</span>
                  </div>
                  <div className="ecl-discussion-thread__stats-item">
                    <span
                      dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon ecl-icon--xs ecl-discussion-thread__stats-item-icon')}}
                    />
                    <span className="ecl-discussion-thread__stats-item-label">Reactions</span>
                    <span className="ecl-discussion-thread__stats-item-value">{this.props.result.its_discussion_total_comments || 0}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {!this.props.isAnonymous && this.state.comment.text && this.state.comment.text.length > 0 && <div className="ecl-discussion-thread__featured-items">
             <Comment comment={this.state.comment} />
          </div>}
        </div>
    </div>);
  }
}

export default DiscussionResultItem;
