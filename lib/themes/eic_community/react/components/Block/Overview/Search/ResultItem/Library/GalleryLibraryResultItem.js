import React from 'react';
import HighlightLink from "./Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import LikeLink from "./Partials/LikeLink";
import StatisticsFooterResult from "../Partials/StatisticsFooterResult";
import Meta from "./Partials/Meta";

const svg = require('../../../../../../svg/svg')

class GalleryLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);
    const slides = this.props.result.sm_content_gallery_slide_id_array
    const featuredSlide = typeof slides !== "undefined" && slides.length > 0 ? JSON.parse(slides.shift()) : null;

    this.state = {
      isHighlighted: this.props.result.its_flag_highlight_content,
      slides: slides,
      featuredSlide: featuredSlide,
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

    const firstSlides = typeof this.state.slides !== "undefined" ? this.state.slides.slice(0, 3) : [];

    return (<div className="ecl-teaser-overview__item ecl-teaser-overview__item--library">

      <div  className={`ecl-teaser ecl-teaser--gallery ecl-teaser--is-highlightable ${isHighlighted && 'ecl-teaser--is-highlighted'}`}>
        <a href={this.props.result.ss_url}>
          <figure className="ecl-teaser__image-wrapper">
            <div className="ecl-teaser__gallery">
              {this.state.featuredSlide !== null && <div className="ecl-teaser__gallery-featured">
                <img className="ecl-teaser__image" src={this.state.featuredSlide.uri_160} alt={this.state.featuredSlide.legend}/>
              </div>}
              <div className="ecl-teaser__gallery-other">
                {firstSlides.length > 0 && firstSlides.map((slide) => {
                  slide = JSON.parse(slide);
                  return <div key={slide !== null ? slide.uri : ''} className="ecl-teaser__gallery-other-image">
                    <img src={slide !== null ? slide.uri : ''} alt=""/>
                  </div>
                })}
                {typeof this.state.slides !== "undefined" && this.state.slides.length > 3 && <div className="ecl-teaser__gallery-other-more">
                  +{this.state.slides.length - 3}
                </div>}
              </div>
            </div>
          </figure>
        </a>
        <div className="ecl-teaser__main-wrapper">
          <div className="ecl-teaser__meta-header">
            <div className="ecl-teaser__meta-column">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <span className="ecl-content-type-indicator__label">{this.props.translations.label_image}</span>
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
              translation={this.props.translations}
            />
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <LikeLink
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
              translations={this.props.translations}
          />
          <StatisticsFooterResult result={this.props.result} />
        </div>
      </div>

    </div>);
  }
}

export default GalleryLibraryResultItem;
