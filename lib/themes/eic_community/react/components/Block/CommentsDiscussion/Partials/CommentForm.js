import React from 'react';

const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {hasPermission} from "../Services/Permissions";
import Popup from "reactjs-popup";
import EntityTree from "@theme/react/components/Field/EntityTree";
import TaggedUsers from "./TaggedUsers";

class CommentForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      taggedUsers: [],
    };

    this.settings = window.drupalSettings.overview;
    this.updateTaggedUsers = this.updateTaggedUsers.bind(this);
    this.submit = this.submit.bind(this);
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

  render() {
    if (!hasPermission('post_comment')) {
      return <React.Fragment/>;
    }

    return <div className={`ecl-comment-overview__form-wrapper ${this.props.className ?? ''}`}>
      <div className="ecl-comment-form ">
        <div className="ecl-comment-form__author">
          <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
            <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={this.settings.user.url}
                         className="ecl-link ecl-link--standalone ecl-author__link">
                        {this.settings.user.fullname}
                      </a>
                </span>
            </div>
            <div className="ecl-author__aside">
              <a href={this.settings.user.url} className="ecl-author__media-wrapper">
                <figure className="ecl-media-container ecl-author__media">
                  <img alt={this.settings.user.fullname} className="ecl-media-container__media"
                       src={this.settings.user.avatar}/>
                </figure>
              </a>
            </div>
          </div>
        </div>
        <div className="ecl-comment-form__content">
          <div className="ecl-comment-form__main">
            <form onSubmit={(e) => this.submit(e)}>
              <div className="ecl-form-group">
                <label className="ecl-comment-form__textarea-label ecl-form-label"
                       htmlFor="ecl-comment-form-reply">{this.props.title}</label>
                <div className="ecl-comment-form__textarea-wrapper">
                <textarea
                  onChange={(e) => this.props.onChangeCommentText(e.target.value, this.props.parentComment)}
                  value={(this.props.commentText || this.props.commentTextReply) || ''}
                  className="ecl-text-area ecl-comment-form__textarea"
                  id="ecl-comment-form-reply"
                  name=""
                  placeholder={getTranslation('comment_placeholder')}
                  required={true}
                />
                </div>
                <TaggedUsers taggedUsers={this.state.taggedUsers} />
              </div>
              <div className="ecl-comment-form__toolbar">
                <div className="ecl-comment-form__toolbar-main">
                  <button disabled={this.props.loading} className="ecl-button ecl-button--primary ecl-comment-form__submit"
                          type="submit">Publish
                  </button>
                </div>
                <div className="ecl-comment-form__toolbar-aside">
                  <button className="ecl-button ecl-button--ghost ecl-comment-form__attachment ecl-button--as-form-option"
                          type="button">
                  <span className="ecl-button__container">
                    <span className="ecl-button__label" data-ecl-label="true">Tag user</span>
                  <Popup
                    trigger={<span
                      style={{cursor: "pointer"}}
                      dangerouslySetInnerHTML={{__html: svg('user', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                    />}
                    title={this.settings.translations.modal_invite_users_title}
                    modal
                    nested
                  >
                    {close => (
                      <div className="modal">
                        <button className="close" onClick={close}>
                          &times;
                        </button>
                        <div className="header">{this.settings.translations.modal_invite_users_title}</div>
                        <div className="content">
                          <div className="ecl-comment-overview__form-wrapper">

                            <div className="ecl-comment-form ">
                              <div className="ecl-comment-form__content">
                                <div className="ecl-comment-form__main">
                                  <h3>{this.settings.translations.select_users}</h3>
                                  <EntityTree
                                    url={this.settings.users_url}
                                    urlSearch={this.settings.users_url_search}
                                    urlChildren={this.settings.users_url}
                                    targetEntity={'user'}
                                    searchSpecificUsers={1}
                                    selectedTerms={JSON.stringify(this.state.taggedUsers)}
                                    matchLimit={0}
                                    length={25}
                                    disableTop={0}
                                    loadAll={1}
                                    ignoreCurrentUser={0}
                                    isRequired={0}
                                    translations={this.settings.translations}
                                    addElementsToExternal={this.updateTaggedUsers}
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    )}
                  </Popup>
                  </span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  }
}

export default CommentForm;
