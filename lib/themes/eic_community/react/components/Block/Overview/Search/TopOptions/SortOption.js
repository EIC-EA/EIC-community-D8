import React from 'react';
const svg = require('../../../../../svg/svg')

class SortOption extends React.Component {
  constructor(props) {
    super(props);
    let defaultSortOption = window.drupalSettings.overview.default_sorting_option;
    defaultSortOption = defaultSortOption.length > 0 ? defaultSortOption[0] + '__' + defaultSortOption[1] : 'any';
    this.state = {
      value: defaultSortOption
    };

    this.onChange = this.onChange.bind(this);

    this.sourceBundle = window.drupalSettings.overview.source_bundle_id;
    this.translations = window.drupalSettings.translations;

    this.props.updateSort(defaultSortOption);
  }

  onChange(e) {
    const value = e.target.value;
    this.setState({value});
    this.props.updateSort(value);
  }

  render() {
    return <div className="ecl-teaser-overview__sort-options">
      <div className="ecl-form-group"><label className="ecl-form-label">{this.props.translations.sort_by}</label>
        <div className="ecl-select__container ecl-select__container--m">
          <select
            value={this.state.value}
            onChange={this.onChange}
            className="ecl-select ecl-teaser-overview__amount-options-items"
          >
            <option value="any">{this.props.translations.sort_any}</option>
            {Object.entries(this.props.sortSettings).map((value) => {
              value[1] = value[1].replace(/[0-9]/g, '');
              const ascKey = `${value[1]}__ASC`;
              const descKey = `${value[1]}__DESC`;
              return <React.Fragment key={value[1]} >
                {typeof this.translations.sources[this.sourceBundle].sort[ascKey] !== "undefined" && <option value={ascKey}>{`${this.translations.sources[this.sourceBundle].sort[ascKey]}`}</option>}
                {typeof this.translations.sources[this.sourceBundle].sort[descKey] !== "undefined" && <option value={descKey}>{`${this.translations.sources[this.sourceBundle].sort[descKey]}`}</option>}
              </React.Fragment>
            })}
          </select>
          <div className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon--xs ecl-icon--rotate-180')}}>
          </div>
        </div>
      </div>
    </div>
  }
}

export default SortOption;
