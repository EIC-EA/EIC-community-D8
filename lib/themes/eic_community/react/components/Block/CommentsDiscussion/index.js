import React from 'react';
import {Button, Comment, Form, Header} from 'semantic-ui-react'
import timeDifferenceFromNow from "../../../Services/TimeHelper";
import getTranslation from "../../../Services/Translations";
import axios from "axios";
import CircularProgress from "@material-ui/core/CircularProgress";
import NotificationSystem from 'react-notification-system';
import initCollapse from "../../../Services/CollapseOptions";
import CollapseActions from "./Partials/CollapseActions";
import CommentForm from "./Partials/CommentForm";
import LoadMore from "./Partials/LoadMore";
import Answers from "./Partials/Answers";
import {hasPermission} from "./Services/Permissions";

const svg = require('../../../svg/svg')

class CommentsDiscussion extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      comments: [],
      commentText: '',
      commentTextReply: '',
      activeAnswerForm: 0,
      page: 1,
      total: 0,
      totalLoaded: 0,
      childToReload: 0,
    };

    this.wrapperRef = React.createRef();
    this.notificationSystem = React.createRef();

    this.fetchComments = this.fetchComments.bind(this);
    this.addComment = this.addComment.bind(this);
    this.handleClickOutside = this.handleClickOutside.bind(this);
    this.flagComment = this.flagComment.bind(this);
    this.updatePage = this.updatePage.bind(this);
    this.addNotification = this.addNotification.bind(this);
    this.onChangeCommentText = this.onChangeCommentText.bind(this);
    this.updateComment = this.updateComment.bind(this);
    this.deleteComment = this.deleteComment.bind(this);
    this.finishReloadChild = this.finishReloadChild.bind(this);
    this.setActiveCommentForm = this.setActiveCommentForm.bind(this);
  }

  componentDidMount() {
    this.fetchComments();
    document.addEventListener('click', this.handleClickOutside);
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  }

  onChangeCommentText(value, parentComment) {
    if (parentComment) {
      this.setState({
        commentTextReply: value,
      });

      return;
    }

    this.setState({
      commentText: value,
    });
  }

  addNotification(message, level = 'success') {
    const notification = this.notificationSystem.current;
    notification.addNotification({
      message: message,
      level: level,
      position: 'br',
    });
  };

  /**
   * Alert if clicked on outside of element
   */
  handleClickOutside(event) {
    if (this.wrapperRef && !this.wrapperRef.current.contains(event.target)) {
      this.setState({
        activeAnswerForm: 0,
      })
    }
  }

  finishReloadChild() {
    this.setState({
      childToReload: 0,
    })
  }

  addComment(e, parentId = null) {
    e.preventDefault();
    self = this;
    this.setState({
      loading: true,
    })

    axios.post('/api/discussion/' + this.props.discussionId + '/comment', {
        text: parentId ? this.state.commentTextReply : this.state.commentText,
        parentId: parentId,
      },
      {withCredentials: true})
      .then(function (response) {
        self.setState({
          activeAnswerForm: 0,
          childToReload: parentId,
          commentText: parentId ? this.state.commentText : '',
          commentTextReply: parentId ? '' : this.state.commentTextReply,
        });

        self.addNotification('Comment added')
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error');
        console.log(error);
      })
  }

  deleteComment(e, commentId, text, parentId = null) {
    e.preventDefault();
    const self = this;
    this.setState({
      loading: true
    })

    axios.delete('/api/discussion/' + this.props.discussionId + '/delete/' + commentId,
      {withCredentials: true})
      .then(function (response) {
        self.addNotification('Comment deleted')
        this.setState({
          childToReload: parentId,
        });
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error')
        console.log(error);
      })
  }

  updatePage() {
    this.setState({
      page: this.state.page + 1,
    }, this.fetchComments);
  }

  fetchComments(page = null, parentId = null) {
    const self = this;
    this.setState({
      loading: true
    })

    const params = {
      page: page || this.state.page,
      parentId: parentId,
    };

    axios.get('/api/discussion/' + this.props.discussionId + '/comments',
      {
        params: params,
        withCredentials: true,
      })
      .then(function (response) {
        self.setState({
          comments: response.data.comments,
          totalLoaded: response.data.total_loaded,
          total: response.data.total,
          loading: false,
        })

        initCollapse();
      }.bind(this))
      .catch(function (error) {
        this.setState({
          loading: false,
        });
        self.addNotification('An error occured', 'error');
        console.log(error);
      })
  }

  flagComment(commentId, type, flagId, text = null) {
    const self = this;
    this.setState({
      loading: true
    })

    axios.post('/api/discussion/' + this.props.discussionId + '/' + flagId + '/' + commentId + '/' + type,
      {text:text, withCredentials: true})
      .then(function (response) {
        self.addNotification(`Comment ${flagId} ${type}`)
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error')
        this.setState({
          loading: false,
        });
      })
  }

  updateComment(e, commentId, text, parentId = null) {
    e.preventDefault();
    const self = this;
    this.setState({
      loading: true
    })

    axios.patch('/api/discussion/' + this.props.discussionId + '/edit/' + commentId,
      {'text': text, withCredentials: true})
      .then(function (response) {
        self.addNotification('Comment edited')
        this.setState({
          childToReload: parentId,
        });
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error')
        console.log(error);
      })
  }

  setActiveCommentForm(commentId) {
    this.setState({
      activeAnswerForm: commentId,
    });
  }

  render() {
    if (this.state.total === 0) {
      return <div className="ecl-comment-overview__wrapper">
        <span className="ecl-comment-overview__title">{getTranslation('no_results')}</span>
        {this.state.loading &&
        <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader"
                          size={50}/>}
        <CommentForm
          loading={this.state.loading}
          addComment={this.addComment}
          commentText={this.state.commentText}
          onChangeCommentText={this.onChangeCommentText}
        />
        <p className="ecl-comment-overview__no-items">{getTranslation('title')}</p>
      </div>
    }

    return <div className="ecl-comment-overview__wrapper">
      <span className="ecl-comment-overview__title">{getTranslation('title')}</span>
      {this.state.loading &&
      <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader"
                        size={50}/>}
      <CommentForm
        addComment={this.addComment}
        commentText={this.state.commentText}
        onChangeCommentText={this.onChangeCommentText}
        loading={this.state.loading}
      />
      <div ref={this.wrapperRef} className="ecl-comment-overview__items">
        {this.state.comments.map(comment => {
          return <div key={comment.comment_id} className="ecl-comment-overview__item">
            <div className="ecl-comment-thread ">
              <div className="ecl-comment  ecl-comment-thread__comment"
                   id={comment.comment_id}>
                <div className="ecl-comment__author">
                  <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
                    <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={comment.user_url}
                         className="ecl-link ecl-link--standalone ecl-author__link">{comment.user_fullname}</a>                  </span>
                    </div>
                    <div className="ecl-author__aside">
                      <a href={comment.user_url} className="ecl-author__media-wrapper">
                        <figure className="ecl-media-container ecl-author__media"><img alt="Avatar image of Jane Doe"
                                                                                       className="ecl-media-container__media"
                                                                                       src={comment.user_image}/>
                        </figure>
                      </a>
                    </div>
                  </div>
                </div>

                <div className="ecl-comment__content">
                  <header className="ecl-comment__header">
                    <div className="ecl-comment__header-main">

                      <div className="ecl-comment__author-info">
                        <a href={comment.user_url}
                           className="ecl-link ecl-link--standalone ecl-comment__author-name">{comment.user_fullname}</a>
                      </div>
                      <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
                      <span
                        dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                      />
                        <time className="ecl-timestamp__label">{timeDifferenceFromNow(comment.created_timestamp)}</time>
                      </div>
                    </div>
                    <CollapseActions deleteComment={this.deleteComment} flagComment={this.flagComment} updateComment={this.updateComment} comment={comment}/>
                  </header>

                  <div className="ecl-comment__main">
                    <div className="ecl-editable-wrapper">
                      {comment.text}
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
                            onClick={(e) => this.flagComment(comment.comment_id, comment.likes.hasAccountLike ? 'unflag' : 'flag', 'like_comment')}
                          /> : <a style={{textDecoration: 'none'}} dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}} />}
                          <span className="ecl-comment__stat-label">Likes</span>
                          <span className="ecl-comment__stat-value">{comment.likes.total}</span>
                        </div>
                      </div>
                    </div>
                  </footer>
                  <Comment.Actions>

                    <span
                      onClick={(e) => this.setActiveCommentForm(comment.comment_id)}
                      style={{cursor: 'pointer'}}
                      className="ecl-link ecl-link--standalone ecl-breadcrumb-standardised__link">
                      <span className="ecl-link__label">{`${getTranslation('reply_to')} ${comment.user_fullname}`}</span>
                    </span>

                    {this.state.activeAnswerForm === comment.comment_id &&
                    <CommentForm addComment={this.addComment}
                                 parentComment={comment.comment_id}
                                 commentTextReply={this.state.commentTextReply}
                                 onChangeCommentText={this.onChangeCommentText}/>
                    }
                  </Comment.Actions>

                  <Answers
                    comment={comment}
                    loading={this.state.loading}
                    addNotification={this.addNotification}
                    finishReloadChild={this.finishReloadChild}
                    needsToReload={this.state.childToReload === comment.comment_id}
                    childToReload={this.state.childToReload}
                    activeAnswerForm={this.state.activeAnswerForm}
                    commentTextReply={this.state.commentTextReply}
                    addComment={this.addComment}
                    onChangeCommentText={this.onChangeCommentText}
                    setActiveCommentForm={this.setActiveCommentForm}
                    updateComment={this.updateComment}
                    deleteComment={this.deleteComment}
                    discussionId={this.props.discussionId}
                    level={0}
                  />
                </div>

              </div>
            </div>
          </div>
        })}
      </div>
      <NotificationSystem ref={this.notificationSystem}/>
      {this.state.total > this.state.totalLoaded && <LoadMore
        updatePage={this.updatePage}
      />}
    </div>
  }

}

export default CommentsDiscussion
