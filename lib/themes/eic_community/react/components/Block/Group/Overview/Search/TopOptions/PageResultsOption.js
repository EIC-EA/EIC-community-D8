import React from 'react';
const svg = require('../../../../../../../react/svg/svg')

class PageResultsOption extends React.Component {
  constructor(props) {
    super(props);

    //Default value wil be the "normal" option
    let pageOptionValues = [10, 20, 50, 100];

    if (this.props.pageOptions === 'each_10') {
      pageOptionValues = [10, 20, 30, 40, 50];
    }

    if (this.props.pageOptions === 'each_6') {
      pageOptionValues = [6, 12, 18, 24];
    }

    this.state = {
      pageOptionValues
    }

    this.onChange = this.onChange.bind(this);
  }

  onChange(e) {
    this.props.updateResultsPerPage(e.target.value);
  }

  render() {
    return <div className="ecl-teaser-overview__amount-options">
      <div className="ecl-form-group"><label className="ecl-form-label">{this.props.translations.showing}</label>
        <div className="ecl-select__container ecl-select__container--m">
          <select onChange={this.onChange} className="ecl-select ecl-teaser-overview__amount-options-items">
          {this.state.pageOptionValues.map((value) => {
            return <option value={value}>{value}</option>
          })}
        </select>
          <div className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon--xs ecl-icon--rotate-180')}}>
          </div>
        </div>
      </div>
      <span className="ecl-teaser-overview__amount-options-total-wrapper">of&nbsp;
        <span className="ecl-teaser-overview__amount-options-total">{this.props.numFound}</span>
      </span>
    </div>
  }
}

export default PageResultsOption;
