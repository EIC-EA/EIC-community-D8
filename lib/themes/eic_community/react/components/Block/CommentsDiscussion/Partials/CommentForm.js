import React from 'react';
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {hasPermission} from "../Services/Permissions";
import Author from "./components/Author";

const CommentForm = (props) => {
  if (!hasPermission('post_comment')) {
    return <React.Fragment />;
  }

  return <div className={`ecl-comment-overview__form-wrapper ${props.className ?? ''}`}>

    <div className="ecl-comment-form ">
      <Author
        url={window.drupalSettings.overview.user.url}
        avatar={window.drupalSettings.overview.user.avatar}
        fullname={window.drupalSettings.overview.user.fullname}
      />
      <div className="ecl-comment-form__content">
        <div className="ecl-comment-form__main">
          <form onSubmit={(e) => props.addComment(e, props.parentComment)}>
            <div className="ecl-form-group">
              <label className="ecl-comment-form__textarea-label ecl-form-label"
                     htmlFor="ecl-comment-form-reply">{props.title}</label>
              <div className="ecl-comment-form__textarea-wrapper">
                <textarea
                  onChange={(e) => props.onChangeCommentText(e.target.value, props.parentComment)}
                  value={(props.commentText || props.commentTextReply) || ''}
                  className="ecl-text-area ecl-comment-form__textarea"
                  id="ecl-comment-form-reply"
                  name=""
                  placeholder={getTranslation('comment_placeholder')}
                  required={true}
                />
              </div>
            </div>
            <div className="ecl-comment-form__toolbar">
              <div className="ecl-comment-form__toolbar-main">
                <button disabled={props.loading} className="ecl-button ecl-button--primary ecl-comment-form__submit" type="submit">Publish
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
}

export default CommentForm;
