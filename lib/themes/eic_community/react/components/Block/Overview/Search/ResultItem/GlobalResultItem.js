import React from 'react';
const svg = require('../../../../../svg/svg')

class GlobalResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return
    }

    return (
      <div className="ecl-teaser-overview__item ">

        <div className="ecl-teaser ecl-teaser--search  ">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__content">
              <h2 className="ecl-teaser__title">
                <a href={this.props.result.ss_url}>

                  <span className="ecl-teaser__title-overflow"><span>{this.props.result.ss_global_title}</span></span>

                </a>
              </h2>

              <div className="ecl-teaser__details">
                <div className="ecl-teaser__detail">
                  <svg className="ecl-icon ecl-icon--s ecl-teaser__detail-icon" focusable="false" aria-hidden="true">
                  </svg>
                  <a href="?author=amFuZWRvZQ==" className="ecl-teaser__detail-contributor">{this.props.result.ss_group_user_fullname} </a>
                  created a new <a href="#">{this.props.result.ss_global_content_type}</a> page
                  - <div className="ecl-timestamp ecl-timestamp--inherits-color ecl-teaser__timestamp">
                  <time className="ecl-timestamp__label">{this.props.result.ss_global_created_date}</time>
                </div>
                </div>
              </div>

              <p className="ecl-teaser__description">{this.props.result.tm_X3b_en_rendered_item}</p>
            </div>

            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__stats">
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">Comments</span>
                  <span className="ecl-teaser__stat-value">32</span>
                </div>
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">Views</span>
                  <span className="ecl-teaser__stat-value">120</span>
                </div>
                <div className="ecl-teaser__stat">
                  <svg className="ecl-icon ecl-icon--xs ecl-teaser__stat-icon" focusable="false" aria-hidden="true">
                  </svg>
                  <span className="ecl-teaser__stat-label">Likes</span>
                  <span className="ecl-teaser__stat-value">32</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default GlobalResultItem;
