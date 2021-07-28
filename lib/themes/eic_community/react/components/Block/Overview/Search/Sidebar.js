import React from 'react';
import SearchField from "./Field/SearchField";
import CheckboxField from "./Field/CheckboxField";

class Sidebar extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="ecl-base-layout__aside">
        <div className="ecl-filter-sidebar ecl-filter-sidebar--is-ready">
          <div className="ecl-filter-sidebar__main">
            <h2 className="ecl-filter-sidebar__title">
              {this.props.translations.filter}
            </h2>
            <div className="ecl-filter-sidebar__items">
              {this.props.enableSearch &&
              <SearchField translations={this.props.translations} searchSolr={this.props.searchSolr}/>
              }

              {this.props.facets && Object.keys(this.props.facets).length !== 0 && Object.entries(this.props.facets).map((values) => {
                return <div key={values[0]} className="ecl-filter-sidebar__item ecl-filter-sidebar__item--is-collapsible">
                          <span className="ecl-filter-sidebar__item-label" tabIndex="0">
                {window.drupalSettings.translations.sources.facet[values[0]]}

                            <svg className="ecl-icon ecl-icon--xs ecl-filter-sidebar__item-label-icon"
                                 focusable="false" aria-hidden="true"></svg>                              </span>

                  <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
                    <div className="ecl-filter-sidebar__item-fields">
                      {values.length >= 1 && values[1].map((option) => {
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
