import React from 'react';
import PageResultsOption from "./TopOptions/PageResultsOption";
import SortOption from "./TopOptions/SortOption";
import ActiveFacet from "./TopOptions/ActiveFacet";
const svg = require('../../../../svg/svg')

class TopOptions extends React.Component {
  constructor(props) {
    super(props);

    this.clearAll = this.clearAll.bind(this);
  }

  clearAll() {
    this.props.resetFacets();
  }

  render() {
    let activeFacets = {}
    let hasActiveFacets = false;

    for (const [facetKey, value] of Object.entries(this.props.activeFilters)) {
      Object.entries(value).map((value) => {
        //Check if parent key has been assigned once
        if (!activeFacets[facetKey]) {
          activeFacets[facetKey] = {};
        }

        if (value[1]) {
          activeFacets[facetKey][value[0]] = value[0];
          hasActiveFacets = true;
        }
      });
    }

    return <section className="ecl-teaser-overview__options">
      {this.props.overviewTitle && <h2 className="ecl-filter-sidebar__title">
        {this.props.overviewTitle}
      </h2>}
      <div className="ecl-teaser-overview__options-row">
        {this.props.allowPagination > 0 &&
          <PageResultsOption
            numFound={this.props.numFound}
            updateResultsPerPage={this.props.updateResultsPerPage}
            pageOptions={this.props.pageOptions}
            translations={this.props.translations}
          />}
        {this.props.sortSettings.length !== 0 &&
          <SortOption
            updateSort={this.props.updateSort}
            sortSettings={this.props.sortSettings}
            translations={this.props.translations}
          />}
      </div>

      <div className="ecl-teaser-overview__options-row">
        <div className="ecl-teaser-overview__active-filters">
          <span className="ecl-teaser-overview__active-filters-title">{this.props.translations.active_filter}</span>

          <div className="ecl-teaser-overview__active-filters-items">
            {activeFacets && Object.keys(activeFacets).length !== 0 && Object.entries(activeFacets).map((facet) => {
              return Object.entries(facet[1]).map((value) => {
                return <ActiveFacet
                  key={value[0]}
                  value={value[0]}
                  facet={facet[0]}
                  updateFacet={this.props.updateFacet}
                />
              });
            })}
          </div>

          {hasActiveFacets &&
            <button onClick={this.clearAll} className="ecl-button ecl-button--ghost ecl-teaser-overview__active-filters-clear-all" type="submit">
              <span className="ecl-button__container">
                <span className="ecl-button__label" data-ecl-label="true">{this.props.translations.clear_all}</span>
                <svg
                  dangerouslySetInnerHTML={{ __html: svg('clear') }}
                  className="ecl-icon ecl-icon--2xs ecl-button__icon ecl-button__icon--after"
                  focusable="false"
                  aria-hidden="true"
                  data-ecl-icon="" />
              </span>
            </button>
          }
        </div>
      </div>
    </section>
  }
}

export default TopOptions;
