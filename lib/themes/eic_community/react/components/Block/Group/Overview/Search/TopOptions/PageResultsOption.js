import React from 'react';

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
      <div className="ecl-form-group"><label className="ecl-form-label">Showing</label>
        <div className="ecl-select__container ecl-select__container--m">
          <select onChange={this.onChange} className="ecl-select ecl-teaser-overview__amount-options-items">
          {this.state.pageOptionValues.map((value) => {
            return <option value={value}>{value}</option>
          })}
        </select>
          <div className="ecl-select__icon">
            <svg className="ecl-icon ecl-icon--s ecl-icon--rotate-180 ecl-select__icon-shape" focusable="false"
                 aria-hidden="true">
            </svg>
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
