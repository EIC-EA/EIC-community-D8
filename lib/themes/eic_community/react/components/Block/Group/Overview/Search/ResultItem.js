import React from 'react';

class ResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      visibility: this.props.result.ss_group_visibility,
    }
  }

  render() {
    if (!this.props.result) {
      return
    }

    return (
      <div className="ecl-teaser-overview__item">
        <div className="ecl-teaser ecl-teaser--group  ecl-teaser--as-card ecl-teaser--as-card-grey">
          <figure className="ecl-teaser__image-wrapper">
            <img className="ecl-teaser__image"
                 src={`http://localhost:8080${this.props.result.ss_group_teaser}`}
                 alt="Wild sea and rocks"/>
          </figure>


          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header">
              {!this.props.isAnonymous && <span
                className={`ecl-tag ecl-tag--display ecl-tag--is-${this.state.visibility}`}>{this.state.visibility === 'public' ? this.props.translations.public : this.props.translations.private}</span>}
            </div>
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <h2 className="ecl-teaser__title">
                  {this.props.result.ss_group_label}
                </h2>

                <div className="ecl-author ecl-author--is-tiny  ecl-teaser__meta">
                  <div className="ecl-author__information">
            <span className="ecl-author__label ">
                  <a href="?owner=john-doe" className="ecl-link ecl-link--standalone ecl-author__link">John Doe</a>              </span>
                  </div>
                  <div className="ecl-author__aside">
                    <div className="ecl-author__media-wrapper">
                      <figure className="ecl-media-container ecl-author__media"><img alt=""
                                                                                     className="ecl-media-container__media"
                                                                                     src="https://picsum.photos/160"/>
                      </figure>
                    </div>
                  </div>
                </div>

                <div className="ecl-timestamp ">
                  <svg className="ecl-icon ecl-icon--s ecl-timestamp__icon" focusable="false"
                       aria-hidden="true">
                  </svg>
                  <time className="ecl-timestamp__label">Last activity 3 hours ago</time>
                </div>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__stats">
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false"
                       aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">{this.props.translations.members}</span>
                  <span className="ecl-teaser__stat-value">32</span>
                </div>
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false"
                       aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">{this.props.translations.reactions}</span>
                  <span className="ecl-teaser__stat-value">287</span>
                </div>
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false"
                       aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">{this.props.translations.documents}</span>
                  <span className="ecl-teaser__stat-value">8</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default ResultItem;
