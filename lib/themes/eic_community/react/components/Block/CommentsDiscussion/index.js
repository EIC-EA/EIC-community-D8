import React from 'react';
import {Button, Comment, Form, Header} from 'semantic-ui-react'
import timeDifferenceFromNow from "../../../Services/TimeHelper";
import axios from "axios";
import CircularProgress from "@material-ui/core/CircularProgress";
import NotificationSystem from 'react-notification-system';
import initCollapse from "../../../Services/CollapseOptions";
import CollapseActions from "./Partials/CollapseActions";
import Answer from "./Partials/Answer";
import CommentForm from "./Partials/CommentForm";

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
    };

    this.wrapperRef = React.createRef();
    this.notificationSystem = React.createRef();

    this.fetchComments = this.fetchComments.bind(this);
    this.addComment = this.addComment.bind(this);
    this.handleClickOutside = this.handleClickOutside.bind(this);
    this.likeComment = this.likeComment.bind(this);
    this.addNotification = this.addNotification.bind(this);
    this.onChangeCommentText = this.onChangeCommentText.bind(this);
    this.updateComment = this.updateComment.bind(this);
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

  addComment(e, parentId = null) {
    e.preventDefault();
    self = this;
    this.setState({
      loading: true
    })

    axios.post('/api/discussion/10/comment', {
        text: parentId ? this.state.commentTextReply : this.state.commentText,
        parentId: parentId,
      },
      {withCredentials: true})
      .then(function (response) {
        self.setState({
          commentText: parentId ? this.state.commentText : '',
          commentTextReply: parentId ? '' : this.state.commentTextReply,
          activeAnswerForm: 0,
        });

        self.addNotification('Comment added')
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error');
        console.log(error);
      })
  }

  fetchComments() {
    const self = this;
    this.setState({
      loading: true
    })

    axios.get('/api/discussion/10/comments',
      {withCredentials: true})
      .then(function (response) {
        self.setState({
          comments: response.data,
          loading: false,
        })

        initCollapse();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error');
        console.log(error);
      })
  }

  likeComment(commentId, type) {
    const self = this;
    this.setState({
      loading: true
    })

    axios.post('/api/discussion/10/like/' + commentId + '/' + type,
      {withCredentials: true})
      .then(function (response) {
        self.addNotification(`Comment ${type === 'flag' ? 'liked' : 'unliked'}`)
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error')
        console.log(error);
      })
  }

  updateComment(e, commentId, text) {
    e.preventDefault();
    const self = this;
    this.setState({
      loading: true
    })

    axios.patch('/api/discussion/10/edit/' + commentId,
      {'text': text, withCredentials: true})
      .then(function (response) {
        self.addNotification('Comment edited')
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'error')
        console.log(error);
      })
  }

  render() {
    return <div className="ecl-comment-overview__wrapper">
      <span className="ecl-comment-overview__title">Replies</span>
      {this.state.loading &&
      <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader"
                        size={50}/>}
      <CommentForm addComment={this.addComment} commentText={this.state.commentText}
                   onChangeCommentText={this.onChangeCommentText}/>
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
                    <CollapseActions updateComment={this.updateComment} comment={comment}/>
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
                          <a
                            style={{textDecoration: 'none', cursor: 'pointer'}}
                            dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}
                            onClick={(e) => this.likeComment(comment.comment_id, comment.likes.hasAccountLike ? 'unflag' : 'flag')}
                          />
                          <span className="ecl-comment__stat-label">Likes</span>
                          <span className="ecl-comment__stat-value">{comment.likes.total}</span>
                        </div>
                      </div>
                    </div>
                  </footer>
                  <Comment.Actions>
                    <a
                      onClick={(e) => this.setState({activeAnswerForm: comment.comment_id})}>{`Reply to ${comment.user_fullname}`}</a>

                    {this.state.activeAnswerForm === comment.comment_id &&
                    <CommentForm addComment={this.addComment}
                                 parentComment={comment.comment_id}
                                 commentTextReply={this.state.commentTextReply}
                                 onChangeCommentText={this.onChangeCommentText}/>
                    }
                  </Comment.Actions>

                  {Object.entries(comment.children).length !== 0 && Object.entries(comment.children).map(child => {
                    return <Answer key={child[1].comment_id} child={child[1]} likeComment={this.likeComment}/>
                  })}
                </div>

              </div>
            </div>
          </div>
        })}
      </div>
      <NotificationSystem ref={this.notificationSystem}/>
    </div>
  }
}

export default CommentsDiscussion
