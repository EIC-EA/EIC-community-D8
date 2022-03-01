import React from 'react';
import getTranslation from "../../../Services/Translations";
import axios from "axios";
import CircularProgress from "@material-ui/core/CircularProgress";
import initCollapse from "../../../Services/CollapseOptions";
import CommentForm from "./Partials/CommentForm";
import LoadMore from "./Partials/LoadMore";
import TopAnswer from "./Partials/TopAnswer";
import {store} from 'react-notifications-component';
import {getPrefixDiscussionEndpoint} from "./Services/UrlHelper";

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

    this.fetchComments = this.fetchComments.bind(this);
    this.addComment = this.addComment.bind(this);
    this.handleClickOutside = this.handleClickOutside.bind(this);
    this.flagComment = this.flagComment.bind(this);
    this.makeRequest = this.makeRequest.bind(this);
    this.updatePage = this.updatePage.bind(this);
    this.addNotification = this.addNotification.bind(this);
    this.onChangeCommentText = this.onChangeCommentText.bind(this);
    this.updateComment = this.updateComment.bind(this);
    this.deleteComment = this.deleteComment.bind(this);
    this.finishReloadChild = this.finishReloadChild.bind(this);
    this.setActiveCommentForm = this.setActiveCommentForm.bind(this);
    this.handleClickCancel = this.handleClickCancel.bind(this);
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
    store.addNotification({
      message,
      type: level,
      insert: "top",
      container: "bottom-right",
      dismiss: {
        duration: 5000,
        onScreen: true
      },
      animationIn: ["animate__animated", "animate__fadeIn"],
      animationOut: ["animate__animated", "animate__fadeOut"],
    });
  };

  /**
   * Alert if clicked on outside of element
   */
  handleClickOutside(event) {
    if (this.wrapperRef && !this.wrapperRef.current.contains(event.target) && !event.target.getAttribute('class')?.match('Mui')) {
      this.setState({
        activeAnswerForm: 0,
      })
    }
  }

  handleClickCancel() {
    this.setState({
      activeAnswerForm: 0,
    })
  }

  finishReloadChild() {
    this.setState({
      childToReload: 0,
    })
  }

  addComment(e, parentId = null, taggedUsers = []) {
    e.preventDefault();
    self = this;
    this.setState({
      loading: true,
    })

    axios.post(getPrefixDiscussionEndpoint() + '/' + this.props.discussionId + '/comment', {
        text: parentId ? this.state.commentTextReply : this.state.commentText,
        parentId: parentId,
        taggedUsers: taggedUsers,
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
        self.addNotification('An error occured', 'danger');
        console.log(error);
      })
  }

  deleteComment(e, commentId, text, parentId = null) {
    e.preventDefault();
    const self = this;
    this.setState({
      loading: true
    })

    axios.delete(getPrefixDiscussionEndpoint() + '/' + window.drupalSettings.overview.group_id + '/' + this.props.discussionId + '/delete/' + commentId,
      {withCredentials: true})
      .then(function (response) {
        self.addNotification('Comment deleted')
        this.setState({
          childToReload: parentId,
        });
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'danger')
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
      highlightedComment: this.props.highlightedCommentId
    };

    axios.get(getPrefixDiscussionEndpoint() + '/' + this.props.discussionId + '/comments',
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
        self.setState({
          loading: false,
        });
        self.addNotification('An error occured', 'danger');
        console.log(error);
      })
  }

  flagComment(commentId, type, flagId, text = null) {
    const self = this;
    this.setState({
      loading: true
    })

    axios.post(getPrefixDiscussionEndpoint() + '/' +  this.props.discussionId + '/' + flagId + '/' + commentId + '/' + type,
      {text:text, withCredentials: true})
      .then(function (response) {
        self.addNotification(`Comment ${flagId} ${type}`)
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'danger')
        self.setState({
          loading: false,
        });
      })
  }

  makeRequest(entity_type, entity_id, request_type, reason) {
    const self = this;
    this.setState({
      loading: true
    })

    axios.post(`${window.drupalSettings.path.baseUrl}api/request/${request_type}/${entity_type}/${entity_id}`,
      {reason:reason, withCredentials: true})
      .then(response => {
        if(response.data && response.data.result) {
          self.addNotification(`Your request has been sent!`)
          self.fetchComments();
        }else {
          self.addNotification(response.data.message, 'danger')
        }
      })
      .catch(function (error) {
        self.addNotification('An error occurred', 'danger')
      })

    this.setState({
      loading: false
    })
  }

  updateComment(e, commentId, text, parentId = null) {
    e.preventDefault();
    const self = this;
    this.setState({
      loading: true
    })

    axios.patch(getPrefixDiscussionEndpoint() + '/' + this.props.discussionId + '/edit/' + commentId,
      {'text': text, withCredentials: true})
      .then(function (response) {
        self.addNotification('Comment edited')
        this.setState({
          childToReload: parentId,
        });
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        self.addNotification('An error occured', 'danger')
        console.log(error);
      })
  }

  setActiveCommentForm(commentId) {
    this.setState({
      activeAnswerForm: commentId,
    });
  }

  render() {
    return <div className="ecl-comment-overview__wrapper">
      <span className="ecl-comment-overview__title">{getTranslation('title')}</span>
      {
        !window.drupalSettings.overview.is_comment_closed &&
        <CommentForm
          addComment={this.addComment}
          commentText={this.state.commentText}
          onChangeCommentText={this.onChangeCommentText}
          loading={this.state.loading}
        />
      }
      {
        parseInt(this.state.total) === 0 &&
        <p className="ecl-comment-overview__no-items">
          {getTranslation('no_results_title')}
        </p>
      }
      {
        this.state.loading &&
        <CircularProgress
          style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}}
          className="loader"
          size={50}
        />
      }
      <div ref={this.wrapperRef} className="ecl-comment-overview__items">
        {this.state.comments.map(comment => {
          return <TopAnswer
            key={comment.comment_id}
            comment={comment}
            flagComment={this.flagComment}
            makeRequest={this.makeRequest}
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
            handleClickCancel={this.handleClickCancel}
            level={0}
          />
        })}
      </div>
      {this.state.total > this.state.totalLoaded && <LoadMore updatePage={this.updatePage}/>}
    </div>
  }

}

export default CommentsDiscussion
