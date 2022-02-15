import React from 'react';
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {hasPermission} from "../Services/Permissions";
import Author from "./components/Author";
import Popup from "reactjs-popup";
import EntityTree from "@theme/react/components/Field/EntityTree";
import TaggedUsers from "./TaggedUsers";
import TaggedUsersModal from "./PopupComponents/TaggedUsersModal";

class CommentForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      taggedUsers: [],
    };

    this.settings = window.drupalSettings.overview;
    this.updateTaggedUsers = this.updateTaggedUsers.bind(this);
    this.submit = this.submit.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.commentArea = React.createRef()
  }

  submit(e) {
    this.setState({
      taggedUsers: [],
    });

    this.props.addComment(e, this.props.parentComment, this.state.taggedUsers);
  }

  updateTaggedUsers(users) {
    this.setState({
      taggedUsers: users,
    })
  }

  getScrollHeight(elm){
    var savedValue = elm.value
    elm.value = ''
    const baseScrollHeight = elm.scrollHeight
    elm.value = savedValue

    return baseScrollHeight
  }

  handleChange(e) {
    const textarea = e.currentTarget
    const minRows = 2
    const maxRows = 10
    textarea.rows = minRows
    const rows = Math.ceil((textarea.scrollHeight - this.getScrollHeight(textarea)) / 18)
    if (rows < maxRows) {
      textarea.style.cssText = 'overflow:hidden;';
      textarea.rows = rows
    } else {
      textarea.style.cssText = 'overflow:auto;';
      textarea.rows = maxRows
    }

    this.props.onChangeCommentText(e.target.value, this.props.parentComment)
  }

  render() {
    if (!hasPermission('post_comment')) {
      return <React.Fragment/>;
    }

    return <div className={`ecl-comment-overview__form-wrapper ${this.props.className ?? ''}`}>
      <div className="ecl-comment-form ">
        <Author
          url={window.drupalSettings.overview.user.url}
          avatar={window.drupalSettings.overview.user.avatar}
          fullname={window.drupalSettings.overview.user.fullname}
        />
        <div className="ecl-comment-form__content">
          <div className="ecl-comment-form__main">
            <form onSubmit={(e) => this.submit(e)}>
              <div className="ecl-form-group">
                <label className="ecl-comment-form__textarea-label ecl-form-label"
                       htmlFor="ecl-comment-form-reply">{this.props.title}</label>
                <div className="ecl-comment-form__textarea-wrapper">
                <textarea
                  onChange={this.handleChange}
                  value={(this.props.commentText || this.props.commentTextReply) || ''}
                  className="ecl-text-area ecl-comment-form__textarea"
                  id="ecl-comment-form-reply"
                  name=""
                  placeholder={getTranslation('comment_placeholder')}
                  required={true}
                  rows={2}
                />
                </div>
                <TaggedUsers taggedUsers={this.state.taggedUsers} />
              </div>
              <div className="ecl-comment-form__toolbar">
                <div className="ecl-comment-form__toolbar-main">
                  <button disabled={this.props.loading} className="ecl-button ecl-button--primary ecl-comment-form__submit"
                          type="submit">Publish
                  </button>
                  {
                    this.props.handleClickCancel &&
                    <button
                      disabled={this.props.loading}
                      className="ecl-button ecl-button--ghost ecl-comment-form__submit"
                      onClick={e => {e.preventDefault(); this.props.handleClickCancel()}}
                    >
                      cancel
                    </button>
                  }


                </div>
                <TaggedUsersModal settings={this.settings} taggedUsers={this.state.taggedUsers} updateTaggedUsers={this.updateTaggedUsers} />
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  }
}

export default CommentForm;
