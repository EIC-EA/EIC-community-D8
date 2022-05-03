import React from "react";
import Popup from "reactjs-popup";
import "reactjs-popup/dist/index.css";
import getTranslation from "../../../../Services/Translations";
import Actions from "./PopupComponents/Actions";
import PopupLayout from "./PopupComponents/Layout";

class PopupRequestForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      reason: "",
    };

    this.submit = this.submit.bind(this);
  }

  submit(e, closeFunction) {
    closeFunction();
    this.props.makeRequest(
      "comment",
      this.props.comment.comment_id,
      this.props.type,
      this.state.reason
    );
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
      >
        {(close) => (
          <PopupLayout close={close} title={this.props.title}>
            <form className="ecl-form " onSubmit={(e) => this.submit(e, close)}>
              <div className="ecl-form-group">
                <label
                  className="ecl-form-label"
                  htmlFor="ecl-comment-form-reply"
                >
                  {getTranslation("reason")}
                </label>

                <textarea
                  onChange={(e) => this.setState({ reason: e.target.value })}
                  value={this.state.reason}
                  id="ecl-comment-form-reply"
                  className="ecl-text-area ecl-text-area--full"
                  name=""
                  rows="3"
                  placeholder="Type your message here..."
                />
              </div>
              <Actions close={close} />
            </form>
          </PopupLayout>
        )}
      </Popup>
    );
  }
}

export default PopupRequestForm;
