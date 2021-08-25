import React from 'react';
import DeleteActivityStream from "../DeleteActivityStream";
import Footer from "../../Overview/Search/ResultItem/ActivityStream/Footer";

class ResultItemWrapper extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const fullname = `${this.props.result.ss_author_first_name} ${this.props.result.ss_author_last_name}`;

    return <div className="ecl-activity-stream__item">
      <DeleteActivityStream
        showDeleteModal={this.props.showDeleteModal}
        messageId={this.props.result.its_message_id}
      />
      <div className="ecl-activity-stream__item__main">
        <div className="ecl-activity-stream__item__image-wrapper">
          <div className="ecl-author ecl-author--is-medium  ecl-author--is-minimal">
            <div className="ecl-author__information">
              <span className="ecl-author__label ">{fullname}</span>
            </div>
            <div className="ecl-author__aside">
              <a href={this.props.result.ss_global_user_url} className="ecl-author__media-wrapper">
                <figure className="ecl-media-container ecl-author__media">
                  <img alt={fullname}
                       className="ecl-media-container__media"
                       src={this.props.result.ss_author_profile_picture}
                  />
                </figure>
              </a>
            </div>
          </div>
        </div>

        <div className="ecl-activity-stream__item__content">
          <div className="ecl-activity-stream__item__type">
            {this.props.children}
          </div>
        </div>
      </div>
      <Footer result={this.props.result}/>
    </div>
  }
}

export default ResultItemWrapper;
