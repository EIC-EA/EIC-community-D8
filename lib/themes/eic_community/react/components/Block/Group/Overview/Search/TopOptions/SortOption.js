import React from 'react';

class SortOption extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <div className="ecl-teaser-overview__sort-options">
      <div className="ecl-form-group"><label className="ecl-form-label">Sort by</label>
        <div className="ecl-select__container ecl-select__container--m"><select
          className="ecl-select ecl-teaser-overview__amount-options-items">
          <option value="any">- Any -</option>
          <option value="newest">Newest</option>
          <option value="oldest">Oldest</option>
        </select>
          <div className="ecl-select__icon">
            <svg className="ecl-icon ecl-icon--s ecl-icon--rotate-180 ecl-select__icon-shape" focusable="false"
                 aria-hidden="true">
            </svg>
          </div>
        </div>
      </div>
    </div>
  }
}

export default SortOption;
