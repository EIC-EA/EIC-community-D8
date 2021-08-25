import React from 'react';
import axios from "axios";
import LoadMore from './LoadMore';
import DiscussionStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/DiscussionStreamResultItem";
import DocumentStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/DocumentStreamResultItem";
import CommentStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/CommentStreamResultItem";
import WikiStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/WikiStreamResultItem";

class ActivityStream extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      results: {},
      total: 0,
      numDocLoaded: 0,
      page: 1,
      translations: JSON.parse(this.props.translations)
    };

    this.types = {
      comment: CommentStreamResultItem,
      document: DocumentStreamResultItem,
      discussion: DiscussionStreamResultItem,
      wiki_page: WikiStreamResultItem,
      //if node, put default comment
      node: CommentStreamResultItem
    }

    this.changePage = this.changePage.bind(this);
    this.searchSolr = this.searchSolr.bind(this);
  }

  componentDidMount() {
    this.searchSolr();
  }

  changePage(page) {
    this.setState({
      page: page,
    }, this.searchSolr)
  }

  searchSolr() {
    const self = this;

    const baseParams = {
      page: this.state.page,
      datasource: this.props.datasource,
      source_class: this.props.sourceClass,
      current_group: this.props.currentGroup
    }
    let searchParam = {}

    this.setState({
      loading: true
    })

    const params = Object.assign({}, baseParams, searchParam);

    axios.get(this.props.solrUrl, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        this.setState({
          results: response.data.response,
          total: response.data.response.numFound,
          loading: false,
          numDocLoaded: response.data.response.docs.length
        })
      }.bind(this))
      .catch(function (error) {
        console.log(error);
      })
  }

  render() {
    if (this.state.results.length === 0) {
      return <section className="ecl-editorial-article__content">
        <div className="ecl-activity-stream">
          <h3 className="ecl-activity-stream__title">{this.props.translations.block_title}</h3>

          <div className="ecl-activity_stream__items-wrapper">
            <p>{this.state.translations.no_results}</p>;
          </div>
        </div>
      </section>
    }

    return <section className="ecl-editorial-article__content">
      <div className="ecl-activity-stream">
        <h3 className="ecl-activity-stream__title">{this.props.translations.block_title}</h3>

        <div className="ecl-activity_stream__items-wrapper">
          {this.state.results && this.state.results.docs && this.state.results.docs.map((value, index) => {
            const ResultComponent = this.types[value.ss_type];
            return <ResultComponent
              key={index}
              result={value}
              translations={this.state.translations}
            />
          })}
        </div>
      </div>

      {this.state.numDocLoaded < this.state.total && <footer className="ecl-activity-stream__footer">
        <LoadMore
          changePage={this.changePage}
          results={this.state.results}
          page={this.state.page}
          translations={this.state.translations}
        />
      </footer>}

    </section>
  }
}

export default ActivityStream
