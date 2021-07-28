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
      resultsPerPage: this.props.pageOptions === 'each_6' ? 6 : 10,
      pageOptions: this.props.pageOptions,
      translations: JSON.parse(this.props.translations),
      datasource: this.props.datasource,
      results: {},
      searchText: this.props.searchString,
      sortValue: '',
      facets: {},
      facetsValue: {},
      page: 1,
      total: 0
    };
    this.searchSolr = this.searchSolr.bind(this);
    this.changePage = this.changePage.bind(this);
    this.updateSort = this.updateSort .bind(this);
    this.updateFacet = this.updateFacet.bind(this);
    this.resetFacets = this.resetFacets.bind(this);
    this.updateResultsPerPage = this.updateResultsPerPage.bind(this);
  }

  componentDidMount() {
    this.searchSolr(this.state.searchText);
  }

  changePage(page) {
    this.searchSolr(this.state.searchText, page);
  }

  updateSort(value) {
    this.state.sortValue = value;
    this.searchSolr(this.state.searchText, this.state.page);
  }

  updateFacet(name, value, facet) {
    //Check if parent key has been assigned once
    if (!this.state.facetsValue[facet]) {
      this.state.facetsValue[facet] = {};
    }

    this.state.facetsValue[facet][name] = value;
    this.searchSolr(this.state.searchText, this.state.page);
  }

  resetFacets() {
    this.state.facetsValue = {};
    this.searchSolr(this.state.searchText, this.state.page);
  }

  updateResultsPerPage(value) {
    this.state.resultsPerPage = value;
    this.searchSolr(this.state.searchText, this.state.page);
  }

  searchSolr(searchText = false, page = 1) {
    const self = this;

    const baseParams = {
      page,
      facets_value: this.state.facetsValue,
      search_value: searchText || '',
      sort_value: this.state.sortValue,
      offset: this.state.resultsPerPage,
      facets_options: this.state.facetSettings,
      datasource: this.state.datasource,
      source_class: this.props.sourceClass,
      current_group: this.props.currentGroup
    }
    let searchParam = {}

    const params = Object.assign({}, baseParams, searchParam);

    axios.get(this.state.urlApi, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        self.setState({
          results: response.data.response,
          total: response.data.response.numFound,
          facets: response.data.facet_counts.facet_fields,
          searchText,
          page
        })
      })
      .catch(function (error) {
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
      <div className="ecl-viewport__middle">
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
                  />
                </div>
                <Pagination
                  total={Math.ceil(this.state.total / this.state.resultsPerPage)}
                  page={this.state.page}
                  changePage={this.changePage}
                />
              </main>
            </div>
            <Sidebar
              enableSearch={this.state.enableSearch}
              translations={this.state.translations}
              facets={this.state.facets}
              updateFacet={this.updateFacet}
              searchSolr={this.searchSolr}
              facetsValue={this.state.facetsValue}
              searchString={this.props.searchString}
            />
          </div>
        </div>
      </div>
    );
  }
}

export default Overview;
