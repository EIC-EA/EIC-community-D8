import React from 'react';
import PageResultsOption from "./TopOptions/PageResultsOption";
import SortOption from "./TopOptions/SortOption";
import ActiveFacet from "./TopOptions/ActiveFacet";

class TopOptions extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    let activeFacets = {}

    for (const [key, value] of Object.entries(this.props.activeFilters)) {
      if (value) {
        activeFacets[key] = key;
      }
    }

    return <section className="ecl-teaser-overview__options">
      <div className="ecl-teaser-overview__options-row">
        <PageResultsOption />
        <SortOption />
      </div>

      <div className="ecl-teaser-overview__options-row">
        <div className="ecl-teaser-overview__active-filters">
          <span className="ecl-teaser-overview__active-filters-title">Active Filters</span>

          <div className="ecl-teaser-overview__active-filters-items">
            {activeFacets && Object.keys(activeFacets).length !== 0 && Object.entries(activeFacets).map((value) => {
              return <ActiveFacet value={value[1]} />
            })}
          </div>

          {this.props.activeFilters && Object.keys(this.props.activeFilters).length !== 0 &&
            <button className="ecl-button ecl-button--ghost ecl-teaser-overview__active-filters-clear-all" type="submit">
            <span className="ecl-button__container"><span className="ecl-button__label"
                                                          data-ecl-label="true">Clear all</span><svg
              className="ecl-icon ecl-icon--2xs ecl-button__icon ecl-button__icon--after" focusable="false"
              aria-hidden="true" data-ecl-icon=""></svg></span></button>
          }
        </div>
      </div>
    </section>
  }
}

export default TopOptions;
