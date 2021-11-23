import React from 'react';
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import StatisticsFooterResult from "../Partials/StatisticsFooterResult";

const svg = require('../../../../../../svg/svg')

const Footer = (props) => {
  return <div className="ecl-activity-stream__item__footer">
    <div className="ecl-activity-stream__item__timestamp">
      <div className="ecl-timestamp ">
        <span
          dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
        />
        <time className="ecl-timestamp__label">{timeDifferenceFromNow(Date.parse(props.result.ds_created) / 1000)}</time>
      </div>
    </div>
    <StatisticsFooterResult result={props.result} />
  </div>
}

export default Footer;
