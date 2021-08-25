import React from 'react';
const svg = require('../../../svg/svg')

class LoadMore extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (Object.keys(this.props.results).length === 0 || (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0))
      return '';

    if (Object.keys(this.props.results.docs).length >= this.props.numFound)
      return '';

    return <span style={{cursor: 'pointer'}} onClick={() => this.props.changePage(this.props.page + 1)}
       className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__call-to-action ecl-link--button ecl-link--button-ghost"><span
      className="ecl-link__label">{this.props.translations.load_more}</span>&nbsp;
      <div dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon ecl-icon--xs ecl-icon--rotate-180 ecl-link__icon')}}/>
    </span>
  }
}

export default LoadMore;
