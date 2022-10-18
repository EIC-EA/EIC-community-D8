import React from 'react';
import axios from "axios";
import Answer from "./Answer";
import LoadMore from "./LoadMore";
import initCollapse from "../../../../Services/CollapseOptions";
import CircularProgress from "@material-ui/core/CircularProgress";
import {getPrefixDiscussionEndpoint} from "../Services/UrlHelper";
import getTranslation from "../../../../Services/Translations";

const svg = require('../../../../svg/svg')

class Answers extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      page: 1,
      comments: [],
      parentId: props.comment.comment_id,
      total: 0,
      totalLoaded: 0,
    };

    this.fetchCommentsChildren = this.fetchCommentsChildren.bind(this);
    this.updatePage = this.updatePage.bind(this);
    this.flagComment = this.flagComment.bind(this);
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (!this.props.needsToReload) {
      return;
    }

    this.fetchCommentsChildren();
    this.props.finishReloadChild();
  }

  componentDidMount() {
    this.fetchCommentsChildren();
  }

  updatePage() {
    this.setState({
      page: this.state.page + 1,
    }, this.fetchCommentsChildren)
  }

  flagComment(commentId, type, flagId, text = null) {
    const self = this;
    this.setState({
      loading: true
    })

    axios.post(getPrefixDiscussionEndpoint() + '/' + this.props.discussionId + '/' + flagId + '/' + commentId + '/' + type,
      {text: text, withCredentials: true})
      .then(function (response) {
        let translation = '';
        if (flagId === 'like_comment') {
          translation = type === 'flag' ? getTranslation('comment_liked') : getTranslation('comment_unliked')
        } else {
          translation = `Comment ${flagId} ${type}`
        }
        self.props.addNotification(translation)
        self.fetchCommentsChildren();
      }.bind(this))
      .catch(function (error) {
        self.props.addNotification('An error occured', 'danger')
        console.log(error);
      })
  }

  fetchCommentsChildren() {
    const self = this;
    this.setState({
      loading: true
    })

    const params = {
      page: this.state.page,
      parentId: this.state.parentId,
    };

    axios.get(getPrefixDiscussionEndpoint() + '/' + this.props.discussionId +'/comments',
      {
        params: params,
        withCredentials: true,
      })
      .then(function (response) {
        self.setState({
          comments: response.data.comments,
          total: response.data.total,
          totalLoaded: response.data.total_loaded,
          loading: false,
        })

        initCollapse();
      }.bind(this))
      .catch(function (error) {
        self.props.addNotification('An error occured', 'danger');
        console.log(error);
      })
  }

  render() {
    return <React.Fragment>
      {this.state.loading &&
      <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader"
                        size={50}/>}
      {this.state.comments.length !== 0 && this.state.comments.map(child => {
        return <Answer
          key={child.comment_id}
          child={child}
          parent={this.props.comment}
          flagComment={this.flagComment}
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
          level={this.props.level + 1}
          loading={this.props.loading}
          handleClickCancel={this.props.handleClickCancel}
        />
      })}

      {this.state.total > this.state.totalLoaded && <LoadMore
        updatePage={this.updatePage}
      />}
    </React.Fragment>
  }
}

export default Answers
