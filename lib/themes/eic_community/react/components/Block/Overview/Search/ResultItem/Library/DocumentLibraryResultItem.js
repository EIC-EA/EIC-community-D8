import React from 'react';
import HighlightLink from "./Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import LikeLink from "./Partials/LikeLink";
import StatisticsFooterResult from "../Partials/StatisticsFooterResult";

const svg = require('../../../../../../svg/svg')

class DocumentLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content
    };

    this.updateHighlight = this.updateHighlight.bind(this);
  }

  updateHighlight(isHighlighted) {
    this.setState({
      isHighlighted: isHighlighted,
    })
  }

  render() {
    let isHighlighted = this.state.isHighlighted;
    isHighlighted = typeof isHighlighted !== 'undefined' && isHighlighted !== false;
    const topics = this.props.result.sm_content_field_vocab_topics_string;
    const filenames = this.props.result.sm_filename;

    return (<div
        className={`ecl-teaser ecl-teaser--filelist ecl-teaser--is-highlightable ${isHighlighted && 'ecl-teaser--is-highlighted'}`}>
        <figure className="ecl-teaser__image-wrapper">
          <img className="ecl-teaser__image" src={this.props.result.ss_content_image_url} alt=""/>
        </figure>
        <div className="ecl-teaser__main-wrapper">
          <div className="ecl-teaser__meta-header">
            <div className="ecl-teaser__meta-column">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <span className="ecl-content-type-indicator__label">{this.props.translations.label_file}</span>
              </div>
            </div>
          </div>
          <div className="ecl-teaser__content">
            <h2 className="ecl-teaser__title">
              <a href={this.props.result.ss_url}>
                <span className="ecl-teaser__title-overflow"><span>{this.props.result.tm_global_title}</span></span>
              </a>
            </h2>
            <div className="ecl-teaser__tags">
              {topics && topics.length > 0 && topics.map((topic) => {
                return (<div key={topic} className="ecl-teaser__tag">
                  <span className="ecl-tag ecl-tag--display">{topic}</span>
                </div>)
              })}
            </div>
            <div className="ecl-teaser__files">
              {filenames && filenames.length > 0 && filenames.join(', ')}
            </div>
            <div className="ecl-teaser__meta-content">
              <div className="ecl-teaser__meta-content-item">
                <div className="ecl-timestamp ">
                  <span
                    dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                  />
                  <time
                    className="ecl-timestamp__label">{timeDifferenceFromNow(this.props.result.ss_drupal_timestamp)}</time>
                </div>
              </div>
              <div className="ecl-teaser__meta-content-item">
                <svg className="ecl-icon ecl-icon--s" focusable="false" aria-hidden="true">
                </svg>
                English
              </div>
              <div className="ecl-teaser__meta-content-item">
                <svg className="ecl-icon ecl-icon--s" focusable="false" aria-hidden="true">
                </svg>
                {!this.props.isAnonymous ? (
                  <a href={this.props.result.ss_global_user_url}>{this.props.translations.uploaded_by} {this.props.result.ss_global_fullname}</a>
                ) : (
                  <span>{this.props.translations.uploaded_by} {this.props.result.ss_global_fullname}</span>
                )}
              </div>
            </div>
            <HighlightLink
              updateHighlight={this.updateHighlight}
              isHighlighted={isHighlighted}
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
              isFlaggable={true}
            />
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <LikeLink translations={this.props.translations} />
          <StatisticsFooterResult result={this.props.result} />
        </div>
      </div>
    );
  }
}

export default DocumentLibraryResultItem;
