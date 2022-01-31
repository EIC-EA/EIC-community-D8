import React from 'react';
import {getMonthByIndex, timeDifferenceFromNow} from "../../../../Services/TimeHelper";
import Answers from "./Answers";
import CommentForm from "./CommentForm";
import CollapseActions from "./CollapseActions";
import {CommentAction} from "semantic-ui-react";
import getTranslation from "../../../../Services/Translations";
import {hasPermissionApi, isCommentHidden} from "../Services/Permissions";
import TimeStatus from "./TimeStatus";
import Author from "./components/Author";
import TaggedUsers from "./TaggedUsers";

const svg = require('../../../../svg/svg')

class Answer extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canLike: false,
    };
  }

  componentDidMount() {
    const self = this;

    hasPermissionApi(this.props.child, 'like_comment').then(hasPermission => {
      self.setState({canLike: hasPermission});
    });
  }

  render() {
    const child = this.props.child;

    return <div className="ecl-comment-thread">
      <div className="ecl-comment ecl-comment--is-reply ecl-comment-thread__comment" id={child.comment_id}>
        <Author
          url={child.user_url}
          avatar={child.user_image}
          fullname={child.user_fullname}
        />
        <div className="ecl-comment__content">
          {parseInt(child.is_soft_delete) === 0 && <header className="ecl-comment__header">
            <div className="ecl-comment__header-main">

              <div className="ecl-comment__author-info">
                <a href={child.user_url}
                   className="ecl-link ecl-link--standalone ecl-comment__author-name">{child.user_fullname}</a>
                <span className="ecl-comment__origin">{getTranslation('in_reply_to')}&nbsp;</span>
                <a href={`${this.props.parent.user_url}`}
                   className="ecl-link ecl-link--standalone ecl-comment__author-name">{this.props.parent.user_fullname}</a>
              </div>
              <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
                <TimeStatus comment={child} />
              </div>
            </div>

            <CollapseActions
              parentId={this.props.parent.comment_id}
              flagComment={this.props.flagComment}
              updateComment={this.props.updateComment}
              deleteComment={this.props.deleteComment}
              comment={child}
            />
          </header>}

          <div className="ecl-comment__main">
            <div className="ecl-editable-wrapper">
              {isCommentHidden(child) ? <b>{child.text}</b> : child.text}
            </div>

            <TaggedUsers taggedUsers={child.tagged_users} />
          </div>

          <footer className="ecl-comment__footer">

            <div className="ecl-comment__actions-wrapper">
              {!window.drupalSettings.overview.is_comment_closed && <div className="ecl-comment__actions">
                {this.props.level < 2 && !isCommentHidden(child) &&
                <div className="ecl-comment__action">
                  <CommentAction>
                    <div>
                    <span
                      onClick={(e) => this.props.setActiveCommentForm(child.comment_id)}
                      style={{cursor: 'pointer'}}
                      className="ecl-link ecl-link--standalone ecl-breadcrumb-standardised__link">
                      <span className="ecl-link__label">{`${getTranslation('reply_to')}`}</span>
                    </span>
                    </div>
                  </CommentAction>
                </div>
                }
                <div className="ecl-comment__action">
                  {this.state.canLike ? <a
                    style={{cursor: 'pointer'}}
                    dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon') + (child.likes.hasAccountLike ? 'Unlike' : 'Like')}}
                    onClick={(e) => this.props.flagComment(child.comment_id, child.likes.hasAccountLike ? 'unflag' : 'flag', 'like_comment')}
                  /> : <a style={{textDecoration: 'none'}}
                          dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>}
                </div>
              </div>}
            </div>

            <div className="ecl-comment__stats-wrapper">
              <div className="ecl-comment__stats">
                <div className="ecl-comment__stat">
                  {this.state.canLike ? <span
                    dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}
                  /> : <span dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>}
                  <span className="ecl-comment__stat-label">Likes</span>
                  <span className="ecl-comment__stat-value">{child.likes.total}</span>
                </div>
              </div>
            </div>
          </footer>

          {this.props.activeAnswerForm === child.comment_id &&
          <CommentForm
            addComment={this.props.addComment}
            parentComment={child.comment_id}
            commentTextReply={this.props.commentTextReply}
            onChangeCommentText={this.props.onChangeCommentText}
            loading={this.props.loading}
            className={"ecl-comment-overview__form-wrapper--children"}
            handleClickCancel={this.props.handleClickCancel}
          />
          }
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


    </div>
  }
}

export default Answer;
