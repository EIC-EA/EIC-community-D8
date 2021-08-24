import React from 'react';

const svg = require('../../../../../../svg/svg')
import timeDifferenceFromNow from "../../../../../../Services/TimeHelper";

class Footer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {

    return <div className="ecl-activity-stream__item__footer">
      <div className="ecl-activity-stream__item__timestamp">
        <div className="ecl-timestamp ">
          <svg className="ecl-icon ecl-icon--s ecl-timestamp__icon" focusable="false" aria-hidden="true">
          </svg>
          <time className="ecl-timestamp__label">{timeDifferenceFromNow(Date.parse(this.props.result.ds_created) / 1000)}</time>
        </div>
      </div>
      <div className="ecl-activity-stream__item__stats">
        <div className="ecl-teaser__stats">
          <div className="ecl-teaser__stat">
            <div dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
            <span className="ecl-teaser__stat-label">Reactions</span>
            <span className="ecl-teaser__stat-value">12</span>
          </div>
          <div className="ecl-teaser__stat">
            <div dangerouslySetInnerHTML={{__html: svg('views', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
            <span className="ecl-teaser__stat-label">Views</span>
            <span className="ecl-teaser__stat-value">27</span>
          </div>
          <div className="ecl-teaser__stat">
            <div dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
            <span className="ecl-teaser__stat-label">Likes</span>
            <span className="ecl-teaser__stat-value">13</span>
          </div>
          <div className="ecl-teaser__stat">
            <div dangerouslySetInnerHTML={{__html: svg('documents', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
            <span className="ecl-teaser__stat-label">Downloads</span>
            <span className="ecl-teaser__stat-value">5</span>
          </div>
        </div>
      </div>
    </div>
  }
}

export default Footer;
