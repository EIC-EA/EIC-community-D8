import React from 'react';
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";

const LoadMore = (props) => {
  return <span style={{cursor: 'pointer'}} onClick={() => props.updatePage()}
               className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__call-to-action ecl-link--button ecl-link--button-ghost"><span
    className="ecl-link__label">{getTranslation('load_more')}</span>&nbsp;
    <div dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon ecl-icon--xs ecl-icon--rotate-180 ecl-link__icon')}}/>
  </span>
}

export default LoadMore;
