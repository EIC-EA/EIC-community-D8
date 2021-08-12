import React from 'react';

const svg = require('../../../../../../svg/svg')

class GalleryLibraryResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return
    }

    const slides = this.props.result.sm_content_gallery_slide_id_array;
    const featuredSlide = slides.length > 0 ? JSON.parse(slides.shift()) : null;
    const topics = this.props.result.sm_content_field_vocab_topics_string;

    return (<div className="ecl-teaser-overview__item ">

      <div className="ecl-teaser ecl-teaser--gallery ecl-teaser--is-highlightable  ">
        <figure className="ecl-teaser__image-wrapper">
          <div className="ecl-teaser__gallery">
            {featuredSlide !== null && <div className="ecl-teaser__gallery-featured">
              <img className="ecl-teaser__image" src={featuredSlide.uri} alt={featuredSlide.legend}/>
            </div>}
            <div className="ecl-teaser__gallery-other">
              {slides.length > 0 && slides.map((slide) => {
                slide = JSON.parse(slide);
                return <div className="ecl-teaser__gallery-other-image">
                  <img src={slide !== null ? slide.uri : ''} alt=""/>
                </div>
              })}
              {slides.length > 3 && <div className="ecl-teaser__gallery-other-more">
                `+${slides.length - 3}`
              </div>}
            </div>
          </div>
        </figure>
        <div className="ecl-teaser__main-wrapper">
          <div className="ecl-teaser__meta-header">
            <div className="ecl-teaser__meta-column">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <span className="ecl-content-type-indicator__label">Image</span>
              </div>
            </div>
          </div>
          <div className="ecl-teaser__content">
            <h2 className="ecl-teaser__title">
              <a href={this.props.result.ss_url}>
                <span className="ecl-teaser__title-overflow"><span>{this.props.result.ss_global_title}</span></span>
              </a>
            </h2>
            <div className="ecl-teaser__tags">
              {topics.length > 0 && topics.map((topic) => {
                return (<div className="ecl-teaser__tag">
                  <span className="ecl-tag ecl-tag--display">{topic}</span>
                </div>)
              })}
            </div>
            <div className="ecl-teaser__meta-content">
              <div className="ecl-teaser__meta-content-item">
                <div className="ecl-timestamp ">
                  <svg className="ecl-icon ecl-icon--s ecl-timestamp__icon" focusable="false" aria-hidden="true">
                  </svg>
                  <time className="ecl-timestamp__label">3 hours ago</time>
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
                <a href={this.props.result.ss_global_user_url}>Uploaded by {this.props.result.ss_global_fullname}</a>
              </div>
            </div>
            <div className="ecl-highlight ">
              <a href="#">
                <svg className="ecl-icon ecl-icon--m ecl-highlight__icon" focusable="false" aria-hidden="true">
                </svg>
                <span className="ecl-highlight__label">Highlight</span>
              </a>
            </div>
          </div>
        </div>
        <div className="ecl-teaser__meta-footer">
          <div className="ecl-teaser__like">
            <a href="#">
              <svg className="ecl-icon ecl-icon--xs ecl-teaser__like-icon" focusable="false" aria-hidden="true">
              </svg>
              Like
            </a>
          </div>
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
