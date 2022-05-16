import React from "react";
import Popup from "reactjs-popup";
import "reactjs-popup/dist/index.css";
import getTranslation from "../../../../Services/Translations";
import Actions from "./PopupComponents/Actions";
import PopupLayout from "./PopupComponents/Layout";
import Author from "./components/Author";
import TaggedUsers from "./TaggedUsers";
import TaggedUsersModal from "./PopupComponents/TaggedUsersModal";
import WysiswigEditor from "./components/WysiswigEditor";



class PopupForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      text: props.comment.text || "",
      taggedUsers: this.props.comment.tagged_users || [],
    };
    this.settings = window.drupalSettings.overview;

    this.textarea = React.createRef()

    this.submit = this.submit.bind(this);
    this.updateTaggedUsers = this.updateTaggedUsers.bind(this);
  }

  submit(e, closeFunction) {
    closeFunction();
    this.props.actionComment(
      e,
      this.props.comment.comment_id,
      this.state.text,
      this.props.parentId,
      this.state.taggedUsers
    );
  }

  updateTaggedUsers(users) {
    this.setState({
      taggedUsers: users,
    })
  }

  render() {
    return (
      <Popup
        trigger={
          <span
            style={{ cursor: "pointer" }}
            className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__edit"
            data-comment-id="1"
          >
            {this.props.title}
          </span>
        }
        modal
        nested
        onOpen={() => {this.textarea?.current?.focus()}}
      >
        {(close) => (
          <PopupLayout close={close} title={this.props.title}>
            <form onSubmit={(e) => this.submit(e, close)}>
              <div className="ecl-modal__content">
                <div>
                  <div className="ecl-comment-form ">
                    {this.props.confirmText ? (
                      <p>{this.props.confirmText}</p>
                    ) : (
                      <>
                        <Author
                          url={window.drupalSettings.overview.user.url}
                          avatar={window.drupalSettings.overview.user.avatar}
                          fullname={
                            window.drupalSettings.overview.user.fullname
                          }
                        />
                        <div className="ecl-comment-form__content">
                          <div className="ecl-comment-form__main">
                            <div className="ecl-form-group">
                              <label
                                className="ecl-comment-form__textarea-label ecl-form-label"
                                htmlFor="ecl-comment-form-reply"
                              >
                                {this.props.title}
                              </label>
                              {this.props.showTextArea && (
                                <div>
                                  <div className="ecl-comment-form__textarea-wrapper">
                                    <WysiswigEditor
                                      ref={this.textarea}
                                      value={this.state.text}
                                      onChange={(e) => this.setState({ text: e })}
                                      id="ecl-comment-form-reply"
                                      required={true}
                                      placeholder={getTranslation('comment_placeholder')}
                                      className=""
                                    />
                                  </div>
                                  <TaggedUsers taggedUsers={this.state.taggedUsers}/>
                                  <TaggedUsersModal settings={this.settings} taggedUsers={this.state.taggedUsers} updateTaggedUsers={this.updateTaggedUsers} />
                                </div>
                              )}
                            </div>
                          </div>
                        </div>
                      </>
                    )}
                  </div>
                </div>
              </div>
              <Actions close={close} submitLabel={this.props.submitLabel} />
            </form>
          </PopupLayout>
        )}
      </Popup>
    );
  }
}

export default PopupForm;
