import React from 'react';
import Sidebar from "./Search/Sidebar";
import Results from "./Search/Results";
import axios from "axios";
import Pagination from "./Search/Pagination";
import TopOptions from "./Search/TopOptions";
import SearchField from "./Search/Field/SearchField";
import { getParamsFromUrl } from "../../Utils/Url";
import LoadMore from "../ActivityStream/LoadMore";
import {addParamsToUrl} from '../../Utils/Url'

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
      dateFrom: null,
      dateEnd: null,
      sortValue: '',
      facets: {},
      facetsValue: {},
      page: getParamsFromUrl('page') || 1,
      total: 0,
      suggestions: {},
      loading: true,
      initiated: false
    };
    this.layoutClassMapping = {
      default: 'ecl-teaser-overview',
      columns: 'ecl-teaser-overview ecl-teaser-overview--has-columns',
      compact: 'ecl-teaser-overview ecl-teaser-overview--has-compact-layout',
      columns_compact: 'ecl-teaser-overview ecl-teaser-overview--has-compact-layout ecl-teaser-overview--has-columns',
      three_columns: 'ecl-teaser-overview--has-columns ecl-teaser-overview--has-3-columns',
    }

    this.searchSolr = this.searchSolr.bind(this);
    this.changePage = this.changePage.bind(this);
    this.updateSort = this.updateSort.bind(this);
    this.updateFacet = this.updateFacet.bind(this);
    this.updateSearchText = this.updateSearchText.bind(this);
    this.updateDateRange = this.updateDateRange.bind(this);
    this.resetFacets = this.resetFacets.bind(this);
    this.updateResultsPerPage = this.updateResultsPerPage.bind(this);
    this.handleCalendarClose = this.handleCalendarClose.bind(this);
    this.prefilterFacets = this.prefilterFacets.bind(this);
    this.firstEl = React.createRef();
  }

  prefilterFacets() {
    if (this.props.prefilterMyInterests) {
      this.updateFacet('my_interests', true, 'interests', false);
    }

    if (this.props.prefilters) {
      Object.entries(this.props.prefilters).forEach(prefilter => {
        const filterId = prefilter[0];

        prefilter[1].forEach(value => {
          this.updateFacet(value, true, filterId, false);
        });
      });
    }
  }

  componentDidMount() {
    this.prefilterFacets()
    this.searchSolr();
  }

  changePage(page, isLoadMore) {
    if (!isLoadMore) {
      this.firstEl.current.scrollIntoView()
    }

    this.setState({
      page: page
    }, function () {
      this.searchSolr();
    });
  }

  updateSort(value) {
    this.state.sortValue = value;
    if (this.state.initiated) {
      this.searchSolr();
    }
  }

  updateFacet(name, value, facet, search = true) {
    //Check if parent key has been assigned once
    if (!this.state.facetsValue[facet]) {
      this.state.facetsValue[facet] = {};
    }

    this.state.facetsValue[facet][name] = value;

    if (search) {
      this.setState({
        page: 1
      })
      this.searchSolr();
      addParamsToUrl('page',1)
    }
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
      searchText,
      page: 1
    })

    clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.searchSolr();
      addParamsToUrl('page',1)
    }, 500);
  }

  updateDateRange(dates, clear) {
    this.setState(
      {
        dateFrom: dates[0],
        dateEnd: dates[1],
      }, () => {
        if (clear === true) { this.searchSolr() }
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
      current_group: this.props.currentGroup,
      userIdFromRoute: this.props.userIdFromRoute
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
        self.setState({
          results: response.data.response,
          suggestions: response.data.spellcheck ? response.data.spellcheck.collations : {},
          total: response.data.response.numFound,
          facets: response.data.facet_counts.facet_fields,
          loading: false,
          initiated: true
        })
      }.bind(this))
      .catch(function (error) {
        console.log(error);
      })
  }

  render() {
    let layoutClass = `ecl-teaser-overview ${this.layoutClassMapping[this.props.layout]}`;

    return (
      <div className="ecl-viewport__middle" ref={this.firstEl}>
        <div className="ecl-base-layout ecl-base-layout--contain ">
          {
            this.props.isSearchOverview &&
            <SearchField
              searchText={this.state.searchText}
              translations={this.state.translations}
              showLabel={false}
              isFullWidth={true}
              updateSearchText={this.updateSearchText}
              bundle={this.props.bundle}
            />
          }
          <div className="ecl-base-layout__content">
            <div className="ecl-base-layout__main">
              <main>
                <div className={layoutClass + (this.props.loading ? ' ecl-teaser-overview--is-loading' : '')}>
                  <Results
                    bundle={this.props.bundle}
                    isAnonymous={this.props.isAnonymous}
                    translations={this.state.translations}
                    results={this.state.results}
                    datasource={this.state.datasource}
                    currentGroupId={this.props.currentGroup}
                    isGroupOwner={this.props.isGroupOwner}
                    initiated={this.state.initiated}
                    groupAdmins={this.props.groupAdmins}
                    loading={this.state.loading}
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
                    overviewTitle={this.props.overviewTitle}
                  />
                </div>
                {(this.props.allowPagination > 0 && this.state.results.numFound !== 0) &&
                  <Pagination
                    total={Math.ceil(this.state.total / this.state.resultsPerPage)}
                    page={this.state.page}
                    changePage={this.changePage}
                  />}
                {(this.state.results.numFound !== 0 && this.state.results?.docs?.length < this.state.total && this.props.allowPagination === 0) &&
                <LoadMore
                    changePage={(page) => this.changePage(page, true)}
                    results={this.state.results}
                    page={this.state.page}
                    translations={this.state.translations}
                  />}
              </main>
            </div>
            {Object.keys(this.state.facets).length > 0 && this.state.enableSearch &&
            <Sidebar
              enableDateFilter={this.state.enableDateFilter}
              enableRegistrationFilter={this.props.enableRegistrationFilter}
              dateRange={[this.state.dateFrom, this.state.dateEnd]}
              updateDateRange={this.updateDateRange}
              handleCalendarClose={this.handleCalendarClose}
              enableSearch={this.state.enableSearch && !this.props.isSearchOverview}
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
              enableInviteUserAction={this.props.enableInviteUserAction}
              inviteUserUrl={this.props.inviteUserUrl}
              isSearchOverview={this.props.isSearchOverview}
              isAnonymous={this.props.isAnonymous}
              postContentActions={this.props.postContentActions}
            />}
          </div>
        </div>
      </div>
    );
  }
}

export default Overview;
