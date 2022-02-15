import React from 'react';
import axios from "axios";
import LoadMore from './LoadMore';
import DiscussionStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/DiscussionStreamResultItem";
import DocumentStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/DocumentStreamResultItem";
import CommentStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/CommentStreamResultItem";
import WikiStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/WikiStreamResultItem";
import VideoResultItem from "../Overview/Search/ResultItem/ActivityStream/VideoStreamResultItem";
import CircularProgress from "@material-ui/core/CircularProgress";
import EventStreamResultItem from "../Overview/Search/ResultItem/ActivityStream/EventStreamResultItem";

class ActivityStream extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      results: {},
      total: 0,
      numDocLoaded: 0,
      page: 1,
      translations: JSON.parse(this.props.translations),
      showDeleteModal: false,
      currentItemToDelete: 0,
    };

    this.types = {
      node_comment: CommentStreamResultItem,
      comment: CommentStreamResultItem,
      document: DocumentStreamResultItem,
      gallery: DocumentStreamResultItem,
      discussion: DiscussionStreamResultItem,
      event: EventStreamResultItem,
      wiki_page: WikiStreamResultItem,
      video: VideoResultItem,
      //if node, put default comment
      node: CommentStreamResultItem
    }

    this.changePage = this.changePage.bind(this);
    this.searchSolr = this.searchSolr.bind(this);
    this.showDeleteModal = this.showDeleteModal.bind(this);
    this.deleteActivityStream = this.deleteActivityStream.bind(this);
  }

  componentDidMount() {
    this.searchSolr();
  }

  changePage(page) {
    this.setState({
      page: page,
    }, this.searchSolr)
  }

  showDeleteModal(id) {
    this.setState({
      currentItemToDelete: id,
      showDeleteModal: true
    })
  }

  deleteActivityStream() {
    const self = this;

    this.setState({
      loading: true
    });

    axios.delete(`${window.drupalSettings.path.baseUrl}group/${this.props.currentGroup}/activity-item/${this.state.currentItemToDelete}`, {
      withCredentials: true,
    })
      .then(function (response) {
        this.setState({
          showDeleteModal: false,
          currentItemToDelete: 0,
          loading: false,
        });
        setTimeout(() => {  this.searchSolr(); }, 2500);
      }.bind(this))
      .catch(function (error) {
        console.log(error);
      })
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
        self.setState({
          loading: false,
        })
        console.log(error);
      })
  }

  render() {
    if (this.state.results === 'undefined' || this.state.numDocLoaded === 0) {
      return <section className="ecl-editorial-article__content ecl-editorial-article__content--full-width">
        <div className="ecl-activity-stream">
          <h3 className="ecl-activity-stream__title">{this.state.translations.block_title}</h3>

          <div className="ecl-activity_stream__items-wrapper">
            <React.Fragment>
              <h3 className="ecl-activity-stream__no-content-header">{this.state.translations.no_results_title}</h3>
              <p className="ecl-teaser-overview__no-content-message">{this.state.translations.no_results_body}</p>
            </React.Fragment>
          </div>
        </div>
      </section>
    }

    return <section className="ecl-editorial-article__content ecl-editorial-article__content--full-width">
      {this.state.loading && <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader" size={50} />}
      <div className="ecl-activity-stream">
        <h3 className="ecl-activity-stream__title">{this.state.translations.block_title}</h3>

        <div className="ecl-activity_stream__items-wrapper">
          {this.state.results && this.state.results.docs && this.state.results.docs.map((value, index) => {
            const ResultComponent = this.types[value.ss_type];
            return <ResultComponent
              key={index}
              result={value}
              translations={this.state.translations}
              showDeleteModal={this.showDeleteModal}
              isAnonymous={this.props.isAnonymous}
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

      <div className={`ecl-viewport__modal ${!this.state.showDeleteModal && 'modal-hide'}`}>
        <div className="ecl-viewport__modal__content">
          <span
            onClick={() => this.setState({showDeleteModal: false})}
            style={{cursor: 'pointer'}}
            className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost"
          >
            <span className="ecl-link__label">{this.state.translations.delete_modal_close}</span>&nbsp;
            <svg className="ecl-icon ecl-icon--2xs ecl-link__icon" focusable="false" aria-hidden="true">
            </svg>
          </span>    <h3 className="ecl-activity-stream__modal__title">{this.state.translations.delete_modal_title}</h3>
          <p className="ecl-activity-stream__modal__description">{this.state.translations.delete_modal_desc}</p>
          <div className="ecl-inline-actions">
            <div className="ecl-inline-actions__items">
              <div className="ecl-inline-actions__item">
                <span
                  onClick={() => this.deleteActivityStream()}
                  style={{cursor: 'pointer'}}
                  className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary">
                  {this.state.translations.delete_modal_confirm}
                </span>
              </div>
              <div className="ecl-inline-actions__item">
                <span
                  onClick={() => this.setState({showDeleteModal: false})}
                  style={{cursor: 'pointer'}}
                  className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost"
                >
                  {this.state.translations.delete_modal_cancel}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  }
}

export default ActivityStream
