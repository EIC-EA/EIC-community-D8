import React from 'react';
import svg from '../../../../../svg/svg'

class SearchField extends React.Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    const translations = props.translations;
    this.search_label = translations.custom_search_text !== undefined && translations.custom_search_text[this.props.bundle] !== undefined ?
        translations.custom_search_text[this.props.bundle] :
        translations.search_text;
  }

  handleChange(e) {
    this.props.updateSearchText(e.target.value);
  }

  render() {
    return (
      <div className={`ecl-filter-sidebar__item ecl-filter-sidebar__item--search ${this.props.isFullWidth && 'ecl-filter-sidebar__item--full-width'}`}>
        {
          this.props.showLabel &&
          <span className="ecl-filter-sidebar__item-label" tabIndex="-1">
            {this.search_label}
          </span>
        }

        <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
          <div className="ecl-filter-sidebar__item-fields">
            <div className="ecl-filter-sidebar__item-field">
              <div className="ecl-form-group">
                <input placeholder={this.props.translations.search_text} value={this.props.searchText} onChange={e => this.handleChange(e)} className="ecl-text-input ecl-text-input--m" type="text"/>
                <button
                  type='submit'
                  className="ecl-search__icon"
                  dangerouslySetInnerHTML={{__html: svg('search', ' ecl-icon--xs')}}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default SearchField;
