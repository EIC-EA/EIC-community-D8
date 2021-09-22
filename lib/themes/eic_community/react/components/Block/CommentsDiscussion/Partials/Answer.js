import React from 'react';
import timeDifferenceFromNow from "../../../../Services/TimeHelper";
import Answers from "./Answers";
import CommentForm from "./CommentForm";
import CollapseActions from "./CollapseActions";
import {CommentAction} from "semantic-ui-react";
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {hasPermission} from "../Services/Permissions";

class Answer extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    const child = this.props.child;

    return <div className="ecl-comment-thread">
      <div className="ecl-comment ecl-comment--is-reply ecl-comment-thread__comment" id={child.comment_id}>
        <div className="ecl-comment__author">
          <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
            <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={child.user_url}
                         className="ecl-link ecl-link--standalone ecl-author__link">{child.user_fullname}</a>                  </span>
            </div>
            <div className="ecl-author__aside">
              <a href={child.user_url} className="ecl-author__media-wrapper">
                <figure className="ecl-media-container ecl-author__media"><img
                  alt="Avatar image of Jane Doe" className="ecl-media-container__media"
                  src={child.user_image} /></figure>
              </a>
            </div>
          </div>
        </div>

        <div className="ecl-comment__content">
          <header className="ecl-comment__header">
            <div className="ecl-comment__header-main">

              <div className="ecl-comment__author-info">
                <a href={child.user_url}
                   className="ecl-link ecl-link--standalone ecl-comment__author-name">{child.user_fullname}</a>
                <span className="ecl-comment__origin">{getTranslation('in_reply_to')}</span>
                <a href={`${this.props.parent.user_url}`}
                   className="ecl-link ecl-link--standalone ecl-comment__author-name">{this.props.parent.user_fullname}</a></div>
              <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
                <time className="ecl-timestamp__label">{timeDifferenceFromNow(child.created_timestamp)}</time>
              </div>
            </div>

            <CollapseActions
              parentId={this.props.parent.comment_id}
              flagComment={this.props.flagComment}
              updateComment={this.props.updateComment}
              deleteComment={this.props.deleteComment}
              comment={child}
            />
          </header>

          <div className="ecl-comment__main">
            <div className="ecl-editable-wrapper">
              {child.text}
            </div>

          </div>

          <footer className="ecl-comment__footer">
            <div className="ecl-comment__actions-wrapper">
              <div className="ecl-comment__actions">
                <div className="ecl-comment__action">

                </div>
              </div>
            </div>


            <div className="ecl-comment__stats-wrapper">
              <div className="ecl-comment__stats">
                <div className="ecl-comment__stat">
                  {hasPermission('can_like') ? <a
                    style={{textDecoration: 'none', cursor: 'pointer'}}
                    dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}
                    onClick={(e) => this.props.flagComment(child.comment_id, child.likes.hasAccountLike ? 'unflag' : 'flag', 'like_comment')}
                  /> : <a style={{textDecoration: 'none'}} dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}} />}
                  <span className="ecl-comment__stat-label">Likes</span>
                  <span className="ecl-comment__stat-value">{child.likes.total}</span>
                </div>
              </div>
            </div>
          </footer>
        </div>
      </div>

      <Answers
        comment={child}
        addNotification={this.props.addNotification}
        finishReloadChild={this.props.finishReloadChild}
        needsToReload={this.props.childToReload === child.comment_id}
        childToReload={this.props.childToReload}
        activeAnswerForm={this.props.activeAnswerForm}
        commentTextReply={this.props.commentTextReply}
        addComment={this.props.addComment}
        onChangeCommentText={this.props.onChangeCommentText}
        setActiveCommentForm={this.props.setActiveCommentForm}
        updateComment={this.props.updateComment}
        deleteComment={this.props.deleteComment}
        discussionId={this.props.discussionId}
        level={this.props.level}
        loading={this.props.loading}
      />

      <CommentAction>
        {this.props.level < 2 && <div>
          <span
            onClick={(e) => this.props.setActiveCommentForm(child.comment_id)}
            style={{cursor: 'pointer'}}
            className="ecl-link ecl-link--standalone ecl-breadcrumb-standardised__link">
            <span className="ecl-link__label">{`${getTranslation('reply_to')} ${child.user_fullname}`}</span>
          </span>
        </div>
        }

        {this.props.activeAnswerForm === child.comment_id &&
        <CommentForm
          addComment={this.props.addComment}
          parentComment={child.comment_id}
          commentTextReply={this.props.commentTextReply}
          onChangeCommentText={this.props.onChangeCommentText}
          loading={this.props.loading}
        />
        }
      </CommentAction>

    </div>
  }
}

export default Answer;
