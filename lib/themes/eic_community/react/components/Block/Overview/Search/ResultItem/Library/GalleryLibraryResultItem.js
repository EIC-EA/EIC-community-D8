import React from 'react';
import HighlightLink from "./Partials/HighlightLink";
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import LikeLink from "./Partials/LikeLink";

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

    return (<div className="ecl-teaser-overview__item ">

      <div  className={`ecl-teaser ecl-teaser--gallery ecl-teaser--is-highlightable ${isHighlighted && 'ecl-teaser--is-highlighted'}`}>
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
            <div className="ecl-teaser__meta-content">
              <div className="ecl-teaser__meta-content-item">
                <div className="ecl-timestamp ">
                  <span
                    dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                  />
                  <time className="ecl-timestamp__label">{timeDifferenceFromNow(this.props.result.ss_drupal_timestamp)}</time>
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
                <a href={this.props.result.ss_global_user_url}>{this.props.translations.uploaded_by} {this.props.result.ss_global_fullname}</a>
              </div>
            </div>
            {this.props.isGroupOwner && <HighlightLink
              updateHighlight={this.updateHighlight}
              isHighlighted={isHighlighted}
              currentGroupId={this.props.currentGroupId}
              nodeId={this.props.result.its_content_nid}
            />}
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <LikeLink translations={this.props.translations} />
          <div className="ecl-teaser__stats">
            <div className="ecl-teaser__stat">
              <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
              </svg>
              <span className="ecl-teaser__stat-label">Likes</span>
              <span className="ecl-teaser__stat-value">287</span>
            </div>
            <div className="ecl-teaser__stat">
              <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
              </svg>
              <span className="ecl-teaser__stat-label">Downloads</span>
              <span className="ecl-teaser__stat-value">8</span>
            </div>
            <div className="ecl-teaser__stat">
              <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
              </svg>
              <span className="ecl-teaser__stat-label">Views</span>
              <span className="ecl-teaser__stat-value">120</span>
            </div>
          </div>
        </div>
      </div>

    </div>);
  }
}

export default GalleryLibraryResultItem;
