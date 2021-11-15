import React from 'react';
import Popup from "reactjs-popup";
import 'reactjs-popup/dist/index.css';
import getTranslation from "../../../../Services/Translations";

class PopupRequestForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      reason: '',
    }

    this.submit = this.submit.bind(this);
  }

  submit(e, closeFunction) {
    closeFunction();
    console.log(this.props.reason)
    this.props.makeRequest('comment', this.props.comment.comment_id, this.props.type, this.state.reason)
  }

  render() {
    return <Popup
      trigger={<span
        style={{cursor: 'pointer'}}
        className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__edit"
        data-comment-id="1">{this.props.title}</span>}
      modal
      nested
    >
      {close => (
        <div className="modal">
          <button className="close" onClick={close}>
            &times;
          </button>
          <div className="header">{this.props.title}</div>
          <div className="content">
            <div className="ecl-comment-overview__form-wrapper">

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
                    <form onSubmit={(e) => this.submit(e, close)}>
                      <div className="ecl-form-group">
                        <label className="ecl-comment-form__textarea-label ecl-form-label"
                               htmlFor="ecl-comment-form-reply">{getTranslation('reason')}</label>
                        <div className="ecl-comment-form__textarea-wrapper">
                        <textarea
                          onChange={(e) => this.setState({reason: e.target.value})}
                          value={this.state.reason}
                          className="ecl-text-area ecl-comment-form__textarea" id="ecl-comment-form-reply" name=""
                          placeholder="Type your message here..."
                        />
                        </div>
                      </div>
                      <div className="ecl-comment-form__toolbar">
                        <div className="ecl-comment-form__toolbar-main">
                          <button className="ecl-button ecl-button--primary ecl-comment-form__submit"
                                  type="submit">{getTranslation('submit')}
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </Popup>
  }
}

export default PopupRequestForm;
