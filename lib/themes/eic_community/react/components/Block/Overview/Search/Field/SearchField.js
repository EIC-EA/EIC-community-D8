import React from 'react';

class SearchField extends React.Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  handleChange(e) {
    this.props.searchSolr(e.target.value)
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item">
                          <span className="ecl-filter-sidebar__item-label" tabIndex="-1">
                            {this.props.translations.search_text}
                          </span>

        <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
          <div className="ecl-filter-sidebar__item-fields">
            <div className="ecl-filter-sidebar__item-field">
              <div className="ecl-form-group">
                <input onChange={e => this.handleChange(e)} className="ecl-text-input ecl-text-input--m" type="text"/>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default SearchField;
