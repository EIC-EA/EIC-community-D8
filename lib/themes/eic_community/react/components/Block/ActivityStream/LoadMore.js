import React from 'react';
const svg = require('../../../svg/svg')

const LoadMore = (props) => {
  if (Object.keys(props.results).length === 0 || (props.results.hasOwnProperty('numFound') && props.results.numFound === 0))
    return '';

  if (Object.keys(props.results.docs).length >= props.numFound)
    return '';

  return <span style={{cursor: 'pointer'}} onClick={() => props.changePage(props.page + 1)}
               className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__call-to-action ecl-link--button ecl-link--button-ghost"><span
    className="ecl-link__label">{props.translations.load_more}</span>&nbsp;
    <div dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon ecl-icon--xs ecl-icon--rotate-180 ecl-link__icon')}}/>
  </span>
}

export default LoadMore;
