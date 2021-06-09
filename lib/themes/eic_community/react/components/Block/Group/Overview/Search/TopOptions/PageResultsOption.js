import React from 'react';

class PageResultsOption extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <div className="ecl-teaser-overview__amount-options">
      <div className="ecl-form-group"><label className="ecl-form-label">Showing</label>
        <div className="ecl-select__container ecl-select__container--m"><select
          className="ecl-select ecl-teaser-overview__amount-options-items">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
          <div className="ecl-select__icon">
            <svg className="ecl-icon ecl-icon--s ecl-icon--rotate-180 ecl-select__icon-shape" focusable="false"
                 aria-hidden="true">
            </svg>
          </div>
        </div>
      </div>
      <span className="ecl-teaser-overview__amount-options-total-wrapper">
                    of&nbsp;<span className="ecl-teaser-overview__amount-options-total">83</span>
                  </span>
    </div>
  }
}

export default PageResultsOption;
