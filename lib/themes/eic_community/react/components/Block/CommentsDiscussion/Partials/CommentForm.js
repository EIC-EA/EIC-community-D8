import React from 'react';
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {hasPermission} from "../Services/Permissions";

const CommentForm = (props) => {
  if (!hasPermission('post_comment')) {
    return <React.Fragment />;
  }

  return <div className="ecl-comment-overview__form-wrapper">

    <div className="ecl-comment-form ">
      <div className="ecl-comment-form__author">
        <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
          <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href="?path=user-john-doe"
                         className="ecl-link ecl-link--standalone ecl-author__link">John Doe</a>                  </span>
          </div>
          <div className="ecl-author__aside">
            <a href="?path=user-john-doe" className="ecl-author__media-wrapper">
              <figure className="ecl-media-container ecl-author__media">
                <img alt="" className="ecl-media-container__media" src="https://picsum.photos/64"/>
              </figure>
            </a>
          </div>
        </div>
      </div>
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
