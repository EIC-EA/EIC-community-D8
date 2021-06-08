import React from 'react';
import Sidebar from "./Search/Sidebar";
import Results from "./Search/Results";
import axios from "axios";
import Pagination from "./Search/Pagination";

const PAGE_OFFSET = 4;

class Overview extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      urlApi: this.props.url,
      translations: JSON.parse(this.props.translations),
      results: {},
      activeFilters: {},
      searchText: '',
      facets: {},
      facetsValue: {},
      page: 1,
      total: 0,
    };
    this.searchSolr = this.searchSolr.bind(this);
    this.changePage = this.changePage.bind(this);
    this.updateFacet = this.updateFacet.bind(this);
  }

  componentDidMount() {
    this.searchSolr();
  }

  changePage(page) {
    this.searchSolr(this.state.searchText, page);
  }

  updateFacet(name, value) {
    this.state.facetsValue[name] = value;
    this.searchSolr(this.state.searchText, this.state.page);
  }

  searchSolr(searchText = false, page = 1) {
    const self = this;

    const baseParams = {
      page,
      facets_value: this.state.facetsValue,
      offset: PAGE_OFFSET,
      search_value: searchText ? "ss_group_label:*" + searchText + '*' : '*:*'
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
          facets: response.data.facet_counts.facet_fields.ss_group_topic_name,
          searchText,
          page
        })
      })
      .catch(function (error) {})
  }

  render() {
    return (
      <div className="ecl-viewport__middle">
        <div className="ecl-base-layout ecl-base-layout--contain ">
          <div className="ecl-base-layout__content">
            <div className="ecl-base-layout__main">
              <main>
                <Results isAnonymous={this.props.isAnonymous} translations={this.state.translations} results={this.state.results}/>
                <Pagination total={Math.ceil(this.state.total/PAGE_OFFSET)} page={this.state.page} changePage={this.changePage}/>
              </main>
            </div>
            <Sidebar translations={this.state.translations}  facets={this.state.facets} updateFacet={this.updateFacet} searchSolr={this.searchSolr}/>
          </div>
        </div>
      </div>
    );
  }
}

export default Overview;
