import React from 'react';

const svg = require('../../../../../../svg/svg')
import Footer from "./Footer";

class WikiStreamResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const fullname = `${this.props.result.ss_author_first_name} ${this.props.result.ss_author_last_name}`;

    return <div className="ecl-activity-stream__item">
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

            <span dangerouslySetInnerHTML={{__html: svg('wiki', 'ecl-icon ecl-icon--s ecl-activity-stream__item__icon')}} />
            <span
              className="ecl-activity-stream__item__description"
              dangerouslySetInnerHTML={{__html: `<a href="${this.props.result.ss_global_user_url}">${fullname}</a> ${this.props.result.ss_operation_type} <a href="${this.props.result.ss_path}">${this.props.result.ss_title}</a>`}}
          >
          </span>
          </div>
        </div>

      </div>

      <Footer result={this.props.result}/>
    </div>
  }
}

export default WikiStreamResultItem;
