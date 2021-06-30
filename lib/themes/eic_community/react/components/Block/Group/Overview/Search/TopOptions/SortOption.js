import React from 'react';

class SortOption extends React.Component {
  constructor(props) {
    super(props);
    this.state = {value: 'any'};

    this.onChange = this.onChange.bind(this);
  }

  onChange(e) {
    const value = e.target.value;
    this.setState({value});
    this.props.updateSort(value);
  }

  render() {
    return <div className="ecl-teaser-overview__sort-options">
      <div className="ecl-form-group"><label className="ecl-form-label">Sort by</label>
        <div className="ecl-select__container ecl-select__container--m">
          <select
            value={this.state.value}
            onChange={this.onChange}
            className="ecl-select ecl-teaser-overview__amount-options-items"
          >
            <option value="any">- Any -</option>
            {Object.entries(this.props.sortSettings).map((value) => {
              const ascKey = `${value[1]}__ASC`;
              const descKey = `${value[1]}__DESC`;
              return <React.Fragment>
                <option value={ascKey}>{`${window.drupalSettings.translations.sources.sort[ascKey]}`}</option>
                <option value={descKey}>{`${window.drupalSettings.translations.sources.sort[descKey]}`}</option>
              </React.Fragment>
            })}
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
