import React from 'react';
import ReactDOM from 'react-dom';
import {Button, Comment, Form, Header} from 'semantic-ui-react'
import timeDifferenceFromNow from "../../../Services/TimeHelper";
import axios from "axios";
import CircularProgress from "@material-ui/core/CircularProgress";
import NotificationSystem from 'react-notification-system';

const styleLink = document.createElement("link");
styleLink.rel = "stylesheet";
styleLink.href =
  "https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css";
document.head.appendChild(styleLink);

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
  }

  componentDidMount() {
    this.fetchComments();
    document.addEventListener('click', this.handleClickOutside);
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
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

  handleState(value) {
    this.setState({comment: value})
  }

  addComment(e, data, parentId = null) {
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
          loading: false,
          activeAnswerForm: 0,
        });

        this.addNotification('Comment added')
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        this.addNotification('An error occured', 'error');
        console.log(error);
      })
  }

  fetchComments() {
    this.setState({
      loading: true
    })

    axios.get('/api/discussion/10/comments',
      {withCredentials: true})
      .then(function (response) {
        console.log(response);
        this.setState({
          comments: response.data,
          loading: false,
        })
      }.bind(this))
      .catch(function (error) {
        this.addNotification('An error occured', 'error');
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
        this.setState({
          loading: false,
        })

        this.addNotification(`Comment ${type === 'flag' ? 'liked' : 'unliked'}`)
        self.fetchComments();
      }.bind(this))
      .catch(function (error) {
        this.addNotification('An error occured', 'error')
        console.log(error);
      })
  }

  render() {
    return <div className="ecl-section-wrapper ecl-section-wrapper--is-white contextual-region">
      <div ref={this.wrapperRef} className="ecl-base-layout ecl-base-layout--contain ">
        <Comment.Group>
          <Header as='h3' dividing>
            Comments
          </Header>
          {this.state.loading && <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader" size={50} />}
          {this.state.comments.map(comment => {
            return <Comment key={comment.comment_id}>
              <Comment.Content>
                <Comment.Avatar src={comment.user_image}/>
                <Comment.Author>{comment.user_fullname}</Comment.Author>
                <Comment.Metadata>
                  <span>{timeDifferenceFromNow(comment.created_timestamp)}</span>
                </Comment.Metadata>
                <Comment.Text>{comment.text}</Comment.Text>
                <Comment.Actions>
                  <span dangerouslySetInnerHTML={{__html:`<span>${comment.likes.total} like(s)</span> `}} />
                  {!comment.likes.hasAccountLike && <a onClick={(e) => this.likeComment(comment.comment_id, 'flag')}>Like</a>}
                  {comment.likes.hasAccountLike && <a onClick={(e) => this.likeComment(comment.comment_id, 'unflag')}>Unlike</a>}
                </Comment.Actions>
              </Comment.Content>

              <div style={{backgroundColor: '#f5f5f5', marginLeft: '35px', borderLeft: 'solid 2px'}}>
                {Object.entries(comment.children).length !== 0 && Object.entries(comment.children).map(child => {
                  child = child[1];
                  return <Comment.Group key={child.comment_id}>
                    <Comment.Content>
                      <Comment.Avatar src={child.user_image}/>
                      <Comment.Author>{child.user_fullname}</Comment.Author>
                      <Comment.Metadata>
                        <span>{timeDifferenceFromNow(child.created_timestamp)}</span>
                      </Comment.Metadata>
                      <Comment.Text>{child.text}</Comment.Text>
                      <Comment.Actions>
                        <span dangerouslySetInnerHTML={{__html:`<span>${child.likes.total} like(s)</span> `}} />
                        {!child.likes.hasAccountLike && <a onClick={(e) => this.likeComment(child.comment_id, 'flag')}>Like</a>}
                        {child.likes.hasAccountLike && <a onClick={(e) => this.likeComment(child.comment_id, 'unflag')}>Unlike</a>}
                      </Comment.Actions>
                    </Comment.Content>
                  </Comment.Group>
                })}
              </div>
              <Comment.Actions>
                <a onClick={(e) => this.setState({activeAnswerForm: comment.comment_id})}>{`Reply to ${comment.user_fullname}`}</a>

                {this.state.activeAnswerForm === comment.comment_id && <Form reply onSubmit={(e, data) => this.addComment(e, data, comment.comment_id)}>
                  <Form.TextArea
                    style={{height: '3em'}}
                    value={this.state.commentTextReply}
                    onChange={(e) => this.setState({commentTextReply: e.target.value})}
                  />
                  <Button content='Add Reply' labelPosition='left' icon='edit' primary/>
                </Form>}
              </Comment.Actions>
            </Comment>
          })}

          <Form reply onSubmit={this.addComment}>
            <Form.TextArea
              value={this.state.commentText}
              onChange={(e) => this.setState({commentText: e.target.value})}
            />
            <Button content='Add Comment' labelPosition='left' icon='edit' primary/>
          </Form>
        </Comment.Group>
      </div>
      <NotificationSystem ref={this.notificationSystem} />
    </div>
  }
}

export default CommentsDiscussion
