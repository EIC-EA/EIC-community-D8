import React from 'react';
import SearchField from "./Field/SearchField";
import CheckboxField from "./Field/CheckboxField";
import CheckboxSpecialField from "./Field/CheckboxSpecialField";
import AddLibraryContent from "./Library/AddLibraryContent";

class Sidebar extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const interestsValue = this.props.facetsValue['interests'];
    let suggestion = '';

    if (Object.keys(this.props.suggestions).length !== 0) {
      suggestion = this.props.suggestions[0][1];
    }

    return (
      <div className="ecl-base-layout__aside">
        {'library' === this.props.bundle && <AddLibraryContent translations={this.props.translations} currentGroupUrl={this.props.currentGroupUrl} />}
        <div className="ecl-filter-sidebar ecl-filter-sidebar--is-ready">
          <div className="ecl-filter-sidebar__main">
            <h2 className="ecl-filter-sidebar__title">
              {this.props.translations.filter}
            </h2>
            <div className="ecl-filter-sidebar__items">
              {parseInt(this.props.enableFacetInterests) !== 0 && <CheckboxSpecialField label={'My interests only'} checked={interestsValue && interestsValue.my_interests} key={'my_interests'} facet={'interests'} updateFacet={this.props.updateFacet} value={'my_interests'}/>}
              {parseInt(this.props.enableFacetMyGroups) !== 0 && <CheckboxSpecialField label={'My groups & content only'} checked={interestsValue && interestsValue.my_groups} key={'my_groups'} facet={'interests'} updateFacet={this.props.updateFacet} value={'my_groups'}/>}

              {this.props.enableSearch &&
              <SearchField searchText={this.props.searchText} translations={this.props.translations} updateSearchText={this.props.updateSearchText}/>
              }

              {suggestion.length !== 0 &&
                <div className="ecl-searchform__suggestion"><p>Did you mean <a onClick={() => this.props.updateSearchText(suggestion)}>{suggestion}</a></p></div>
              }

              {this.props.facets && Object.keys(this.props.facets).length !== 0 && Object.entries(this.props.facets).map((values) => {
                return <div key={values[0]} className="ecl-filter-sidebar__item">
                          <span className="ecl-filter-sidebar__item-label" tabIndex="0"style={{
                            borderBottom: '1px solid',
                            borderColor: '#404040',
                            marginBottom: '.75rem',
                            padding: '.75rem 0'
                          }}>
                {window.drupalSettings.translations.sources.facet[values[0]]}

                            <svg className="ecl-icon ecl-icon--xs ecl-filter-sidebar__item-label-icon"
                                 focusable="false" aria-hidden="true"></svg>                              </span>

                  <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
                    <div className="ecl-filter-sidebar__item-fields">
                      {values.length >= 1 && values[1].filter(option => {
                        // If option doesnt have label or results AND if content type facet and bundle library show only certain checkboxes
                        return (option[0] || option[1] !== 0) && (values[0] !== 'ss_global_content_type' || this.props.bundle !== 'library' || ['video', 'document', 'gallery'].includes(option[0]))
                      }).map((option) => {
                        const facetValue = this.props.facetsValue[values[0]];
                        let checked = false;

                        if (facetValue && facetValue[option[0]]) {
                          checked = facetValue[option[0]];
                        }

                        return <CheckboxField checked={checked} key={option[0]} facet={values[0]} updateFacet={this.props.updateFacet} value={option}/>
                      })}
                    </div>
                    <div className="ecl-filter-sidebar__item-options">
                      <button className="ecl-button ecl-button--ghost ecl-filter-sidebar__item-expand"
                              type="submit"><span
                        className="ecl-button__container"><span className="ecl-button__label"
                                                                data-ecl-label="true">Show all</span><svg
                        className="ecl-icon ecl-icon--xs ecl-icon--rotate-180 ecl-button__icon ecl-button__icon--after"
                        focusable="false" aria-hidden="true" data-ecl-icon=""></svg></span>
                      </button>
                    </div>
                  </div>
                </div>
              })}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default Sidebar;
