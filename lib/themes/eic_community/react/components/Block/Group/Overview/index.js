import React from 'react';
import Sidebar from "./Search/Sidebar";
import Results from "./Search/Results";
import axios from "axios";

const API_URL = 'http://localhost:8983/solr/eic/select';

class Overview extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      results: {},
      activeFilters: {},
      searchText: '',
      facets: {}
    };
    this.searchSolr = this.searchSolr.bind(this);
  }

  componentDidMount() {
    this.searchSolr();
  }

  searchSolr(searchText = false) {
    const self = this;
    const datasource = `ss_search_api_datasource:"${this.props.datasource}"`

    const baseParams = {
      'wt': 'json',
      'q': datasource,
      'sort': 'ss_group_label asc',
      'facet.field': 'ss_group_topic_name',
      'facet': 'on',
      'json.nl': 'arrarr'
    }
    let searchParam = {}

    if (searchText) {
      searchText = "ss_group_label:" + searchText
      searchParam = {
        'fq': '*' + searchText + '*'
      }
    }

    const params = Object.assign({}, baseParams, searchParam);

    axios.get(API_URL, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        self.setState({
          results: response.data.response,
          facets: response.data.facet_counts.facet_fields.ss_group_topic_name
        })
        console.log(response.data.facet_counts.facet_fields.ss_group_topic_name);
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  render() {
    return (
      <div className="ecl-viewport__middle">
        <div className="ecl-base-layout ecl-base-layout--contain ">
          <div className="ecl-base-layout__content">
            <Results results={this.state.results}/>
            <Sidebar facets={this.state.facets} searchSolr={this.searchSolr}/>
          </div>
        </div>
      </div>
    );
  }
}

export default Overview;
