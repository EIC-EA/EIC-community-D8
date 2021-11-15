import React from 'react';
import Answers from "./Answers";
import CommentForm from "./CommentForm";
import CollapseActions from "./CollapseActions";
import {Comment} from "semantic-ui-react";
import getTranslation from "../../../../Services/Translations";
import {hasPermissionApi, isCommentHidden} from "../Services/Permissions";
import TimeStatus from "./TimeStatus";

const svg = require('../../../../svg/svg')

class TopAnswer extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canLike: false,
    };
  }

  componentDidMount() {
    const self = this;

    hasPermissionApi(this.props.comment, 'like_comment').then(hasPermission => {
      self.setState({canLike: hasPermission});
    });
  }

  render() {
    return <div key={this.props.comment.comment_id} className="ecl-comment-overview__item">
      <div className="ecl-comment-thread ">
        <div className="ecl-comment  ecl-comment-thread__comment"
             id={this.props.comment.comment_id}>
          <div className="ecl-comment__author">
            <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
              <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={this.props.comment.user_url}
                         className="ecl-link ecl-link--standalone ecl-author__link">{this.props.comment.user_fullname}</a>                  </span>
              </div>
              <div className="ecl-author__aside">
                <a href={this.props.comment.user_url} className="ecl-author__media-wrapper">
                  <figure className="ecl-media-container ecl-author__media"><img alt="Avatar image of Jane Doe"
                                                                                 className="ecl-media-container__media"
                                                                                 src={this.props.comment.user_image}/>
                  </figure>
                </a>
              </div>
            </div>
          </div>

          <div className="ecl-comment__content">
            <header className="ecl-comment__header">
              <div className="ecl-comment__header-main">
                <div className="ecl-comment__author-info">
                  <a href={this.props.comment.user_url}
                     className="ecl-link ecl-link--standalone ecl-comment__author-name">{this.props.comment.user_fullname}</a>
                </div>
                <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
                      <span
                        dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                      />
                  <TimeStatus comment={this.props.comment} />
                </div>
              </div>

              <CollapseActions
                  deleteComment={this.props.deleteComment}
                  flagComment={this.props.flagComment}
                  makeRequest={this.props.makeRequest}
                  updateComment={this.props.updateComment}
                  comment={this.props.comment}
              />
            </header>

            <div className="ecl-comment__main">
              <div className="ecl-editable-wrapper">
                {isCommentHidden(this.props.comment) ? <b>{this.props.comment.text}</b> : this.props.comment.text}
              </div>

            </div>

            <footer className="ecl-comment__footer">
              <div className="ecl-comment__actions-wrapper">
                <div className="ecl-comment__actions">
                  <div className="ecl-comment__action">
                    {!isCommentHidden(this.props.comment) &&
                    <Comment.Actions>
                      <span
                        onClick={(e) => this.props.setActiveCommentForm(this.props.comment.comment_id)}
                        style={{cursor: 'pointer'}}
                        className="ecl-link ecl-link--standalone ecl-breadcrumb-standardised__link">
                        <span className="ecl-link__label">{`${getTranslation('reply_to')} ${this.props.comment.user_fullname}`}</span>
                      </span>
                    </Comment.Actions>}
                  </div>
                  <div className="ecl-comment__action">
                    {this.state.canLike ? <a
                      style={{ cursor: 'pointer'}}
                      dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon') + (this.props.comment.likes.hasAccountLike ? 'Unlike' : 'Like')}}
                      onClick={(e) => this.props.flagComment(this.props.comment.comment_id, this.props.comment.likes.hasAccountLike ? 'unflag' : 'flag', 'like_comment')}
                    /> : <a style={{textDecoration: 'none'}} dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}} />}
                  </div>
                </div>
              </div>

              <div className="ecl-comment__stats-wrapper">
                <div className="ecl-comment__stats">
                  <div className="ecl-comment__stat">
                    {this.state.canLike ? <a
                      style={{textDecoration: 'none', cursor: 'pointer'}}
                      dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}
                      onClick={(e) => this.props.flagComment(this.props.comment.comment_id, this.props.comment.likes.hasAccountLike ? 'unflag' : 'flag', 'like_comment')}
                    /> : <a style={{textDecoration: 'none'}} dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}} />}
                    <span className="ecl-comment__stat-label">Likes</span>
                    <span className="ecl-comment__stat-value">{this.props.comment.likes.total}</span>
                  </div>
                </div>
              </div>
            </footer>

            {this.props.activeAnswerForm === this.props.comment.comment_id &&
            <CommentForm addComment={this.props.addComment}
                         parentComment={this.props.comment.comment_id}
                         commentTextReply={this.props.commentTextReply}
                         onChangeCommentText={this.props.onChangeCommentText}
                         className={"ecl-comment-overview__form-wrapper--children"}
                         />
            }


            <Answers
              comment={this.props.comment}
              loading={this.props.loading}
              addNotification={this.props.addNotification}
              finishReloadChild={this.props.finishReloadChild}
              needsToReload={this.props.childToReload === this.props.comment.comment_id}
              childToReload={this.props.childToReload}
              activeAnswerForm={this.props.activeAnswerForm}
              commentTextReply={this.props.commentTextReply}
              addComment={this.props.addComment}
              onChangeCommentText={this.props.onChangeCommentText}
              setActiveCommentForm={this.props.setActiveCommentForm}
              updateComment={this.props.updateComment}
              deleteComment={this.props.deleteComment}
              discussionId={this.props.discussionId}
              level={0}
            />
          </div>

        </div>
      </div>
    </div>
  }
}

export default TopAnswer;
