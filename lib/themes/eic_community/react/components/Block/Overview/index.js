import React from 'react';
import Sidebar from "./Search/Sidebar";
import Results from "./Search/Results";
import axios from "axios";
import Pagination from "./Search/Pagination";
import TopOptions from "./Search/TopOptions";

class Overview extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      urlApi: this.props.url,
      facetSettings: JSON.parse(this.props.facets),
      sortSettings: JSON.parse(this.props.sorts),
      enableSearch: Boolean(parseInt(this.props.enableSearch)),
      enableDateFilter: Boolean(parseInt(this.props.enableDateFilter)),
      resultsPerPage: this.props.pageOptions === 'each_6' ? 6 : 10,
      pageOptions: this.props.pageOptions,
      translations: JSON.parse(this.props.translations),
      datasource: this.props.datasource,
      results: {},
      searchText: this.props.urlSearchString,
      dateFrom: 0,
      dateEnd: 0,
      sortValue: '',
      facets: {},
      facetsValue: {},
      page: 1,
      total: 0,
      suggestions: {},
      loading: false
    };
    this.searchSolr = this.searchSolr.bind(this);
    this.changePage = this.changePage.bind(this);
    this.updateSort = this.updateSort .bind(this);
    this.updateFacet = this.updateFacet.bind(this);
    this.updateSearchText = this.updateSearchText.bind(this);
    this.updateDateRange = this.updateDateRange.bind(this);
    this.resetFacets = this.resetFacets.bind(this);
    this.updateResultsPerPage = this.updateResultsPerPage.bind(this);
    this.handleCalendarClose = this.handleCalendarClose.bind(this);
    this.firstEl = React.createRef();

    if (this.props.prefilters) {
      Object.entries(this.props.prefilters).forEach(prefilter => {
        const filterId = prefilter[0];

        prefilter[1].forEach(value => {
          this.updateFacet(value, true, filterId);
        });
      });
    }
  }

  componentDidMount() {
    this.searchSolr();
  }

  changePage(page) {
    this.firstEl.current.scrollIntoView()
    this.setState({
      page: page
    }, function() {
      this.searchSolr();
    });
  }

  updateSort(value) {
    this.state.sortValue = value;
    this.searchSolr();
  }

  updateFacet(name, value, facet) {
    //Check if parent key has been assigned once
    if (!this.state.facetsValue[facet]) {
      this.state.facetsValue[facet] = {};
    }

    this.state.facetsValue[facet][name] = value;

    this.searchSolr();
  }

  resetFacets() {
    this.state.facetsValue = {};
    this.searchSolr();
  }

  updateResultsPerPage(value) {
    this.state.resultsPerPage = value;
    this.searchSolr();
  }

  updateSearchText(searchText) {
    this.setState({
      searchText
    })

    clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.searchSolr();
    }, 500);
  }

  updateDateRange(dates) {
    this.setState(
      {
        dateFrom: dates[0],
        dateEnd: dates[1],
      }
    )
  }

  handleCalendarClose() {
    this.searchSolr();
  }

  searchSolr() {
    const self = this;

    const baseParams = {
      page: this.state.page,
      facets_value: this.state.facetsValue,
      search_value: this.state.searchText || '',
      from_date: new Date(this.state.dateFrom).getTime() / 1000,
      end_date: new Date(this.state.dateEnd).getTime() / 1000,
      sort_value: this.state.sortValue,
      offset: this.props.allowPagination ? this.state.resultsPerPage : this.props.loadMoreNumber,
      facets_options: this.state.facetSettings,
      datasource: this.state.datasource,
      source_class: this.props.sourceClass,
      current_group: this.props.currentGroup
    }
    let searchParam = {}

    this.setState({
      loading: true
    })

    const params = Object.assign({}, baseParams, searchParam);

    axios.get(this.state.urlApi, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        this.setState({
          results: response.data.response,
          suggestions: response.data.spellcheck ? response.data.spellcheck.collations : {},
          total: response.data.response.numFound,
          facets: response.data.facet_counts.facet_fields,
          loading: false
        })
      }.bind(this))
      .catch(function (error) {
        console.log(error);
      })
  }

  render() {
    let layoutClass = 'ecl-teaser-overview';

    if (this.props.layout === 'compact') {
      layoutClass = 'ecl-teaser-overview ecl-teaser-overview--has-compact-layout';
    }

    if (this.props.layout === 'columns') {
      layoutClass = 'ecl-teaser-overview ecl-teaser-overview--has-columns';
    }

    if (this.props.layout === 'columns_compact') {
      layoutClass = 'ecl-teaser-overview ecl-teaser-overview--has-compact-layout ecl-teaser-overview--has-columns';
    }

    return (
      <div className="ecl-viewport__middle" ref={this.firstEl}>
        <div className="ecl-base-layout ecl-base-layout--contain ">
          <div className="ecl-base-layout__content">
            <div className="ecl-base-layout__main">
              <main>
                <div className={layoutClass}>
                  <Results
                    bundle={this.props.bundle}
                    isAnonymous={this.props.isAnonymous}
                    translations={this.state.translations}
                    results={this.state.results}
                    datasource={this.state.datasource}
                    currentGroupId={this.props.currentGroup}
                    isGroupOwner={this.props.isGroupOwner}
                  />
                  <TopOptions
                    activeFilters={this.state.facetsValue}
                    sortSettings={this.state.sortSettings}
                    updateSort={this.updateSort}
                    updateFacet={this.updateFacet}
                    resetFacets={this.resetFacets}
                    pageOptions={this.state.pageOptions}
                    updateResultsPerPage={this.updateResultsPerPage}
                    numFound={this.state.total}
                    translations={this.state.translations}
                    allowPagination={this.props.allowPagination}
                  />
                </div>
                {(this.props.allowPagination > 0 && this.state.results.numFound !== 0) &&
                <Pagination
                  total={Math.ceil(this.state.total / this.state.resultsPerPage)}
                  page={this.state.page}
                  changePage={this.changePage}
                />}
              </main>
            </div>
            {Object.keys(this.state.facets).length > 0 && this.state.enableSearch &&
            <Sidebar
              enableDateFilter={this.state.enableDateFilter}
              dateRange={[this.state.dateFrom, this.state.dateEnd]}
              updateDateRange={this.updateDateRange}
              handleCalendarClose={this.handleCalendarClose}
              enableSearch={this.state.enableSearch}
              translations={this.state.translations}
              facets={this.state.facets}
              updateFacet={this.updateFacet}
              updateSearchText={this.updateSearchText}
              facetsValue={this.state.facetsValue}
              searchText={this.state.searchText}
              enableFacetMyGroups={this.props.enableFacetMyGroups}
              enableFacetInterests={this.props.enableFacetInterests}
              suggestions={this.state.suggestions}
              currentGroupUrl={this.props.currentGroupUrl}
              bundle={this.props.bundle}
            />}
          </div>
        </div>
      </div>
    );
  }
}

export default Overview;
