import React from 'react';
import HighlightLink from "./Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import LikeLink from "./Partials/LikeLink";
import StatisticsFooterResult from "../Partials/StatisticsFooterResult";
import Meta from "./Partials/Meta";

const svg = require('../../../../../../svg/svg')

class VideoLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content
    };

    this.updateHighlight = this.updateHighlight.bind(this);
  }

  updateHighlight(isHighlighted) {
    this.setState({
      isHighlighted,
    })
  }

  render() {
    let isHighlighted = this.state.isHighlighted;
    isHighlighted = typeof isHighlighted !== 'undefined' && isHighlighted !== false;
    const topics = this.props.result.sm_content_field_vocab_topics_string;
    const thumbnails =  `<img class="ecl-teaser__image" src="${this.props.result.ss_content_image_url}" alt=""/>  ${svg('play', ' ecl-icon--xl ecl-teaser__image-play-icon')}`

    return (<div className="ecl-teaser-overview__item ecl-teaser-overview__item--library ">

      <div
        className={`ecl-teaser ecl-teaser--video ecl-teaser--is-highlightable ${isHighlighted && 'ecl-teaser--is-highlighted'}`}>
        <a href={this.props.result.ss_url}>
          <figure
            className="ecl-teaser__image-wrapper"
            dangerouslySetInnerHTML={{__html:thumbnails}} />
        </a>
        <div className="ecl-teaser__main-wrapper">
          <div className="ecl-teaser__meta-header">
            <div className="ecl-teaser__meta-column">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <span className="ecl-content-type-indicator__label">{this.props.translations.label_video}</span>
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
              {topics.length > 0 && topics.map((topic) => {
                return (<div key={topic} className="ecl-teaser__tag">
                  <span className="ecl-tag ecl-tag--display">{topic}</span>
                </div>)
              })}
            </div>
            <Meta result={this.props.result} isAnonymous={this.props.isAnonymous} translations={this.props.translations}  />
            <HighlightLink
              updateHighlight={this.updateHighlight}
              isHighlighted={isHighlighted}
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
            />
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <LikeLink translations={this.props.translations} />
          <StatisticsFooterResult result={this.props.result} />
        </div>
      </div>

    </div>);
  }
}

export default VideoLibraryResultItem;
