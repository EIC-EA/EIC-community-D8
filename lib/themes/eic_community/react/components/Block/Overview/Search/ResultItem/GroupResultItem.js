import React from 'react';

const svg = require('../../../../../svg/svg')
import timeDifferenceFromNow from "../../../../../Services/TimeHelper";

class GroupResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return
    }

    let groupVisibilityLabel = '';
    const moderationState = this.props.result.ss_group_moderation_state;
    let groupClass = 'public' !== this.props.result.ss_group_visibility || moderationState !== 'published' ? 'private' : 'public';
    let groupImageFallback = '<use xlink:href="themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#custom--group_circle"></use>';

    if (moderationState !== 'published') {
      groupVisibilityLabel = this.props.translations[moderationState];
    } else {
      groupVisibilityLabel = this.props.result.ss_group_visibility !== 'public' ? this.props.translations.private : this.props.translations.public
    }

    return (
      <div className="ecl-teaser-overview__item" key={this.props.result.ss_group_label_string}>
        <div className="ecl-teaser ecl-teaser--group  ecl-teaser--as-card ecl-teaser--as-card-grey">
          <figure className="ecl-teaser__image-wrapper">
            {this.props.result.ss_group_teaser_url_string !== undefined &&
              <img className="ecl-teaser__image"
                 src={this.props.result.ss_group_teaser_url_string}
                 alt={this.props.result.ss_group_label_string}/>
            }
            {this.props.result.ss_group_teaser_url_string === undefined &&
              <div className="ecl-teaser__image-fallback-wrapper">
                <svg className="ecl-icon ecl-icon--3xl" focusable="false" aria-hidden="true" dangerouslySetInnerHTML={{__html: groupImageFallback }} />
              </div>
            }
          </figure>


          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header">
              {!this.props.isAnonymous && <span
                className={`ecl-tag ecl-tag--display ecl-tag--is-${groupClass}`}>{groupVisibilityLabel}</span>}
            </div>
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <h2 className="ecl-teaser__title">
                  <a href={this.props.result.ss_url}>{this.props.result.ss_group_label_string}</a>
                </h2>

                <div className="ecl-author ecl-author--is-tiny  ecl-teaser__meta">
                  <div className="ecl-author__information">
                    <span className="ecl-author__label ">
                          <a href={this.props.result.ss_global_user_url}
                             className="ecl-link ecl-link--standalone ecl-author__link">
                            {this.props.result.ss_group_user_first_name} {this.props.result.ss_group_user_last_name}
                          </a>
                    </span>
                  </div>
                  <div className="ecl-author__aside">
                    <div className="ecl-author__media-wrapper">
                      <figure className="ecl-media-container ecl-author__media"><img alt=""
                                                                                     className="ecl-media-container__media"
                                                                                     src={this.props.result.ss_group_user_image}/>
                      </figure>
                    </div>
                  </div>
                </div>

                <div className="ecl-timestamp ">
                  <svg className="ecl-icon ecl-icon--s ecl-timestamp__icon" focusable="false"
                       aria-hidden="true">
                  </svg>
                  <time className="ecl-timestamp__label">Last
                    activity {timeDifferenceFromNow(this.props.result.ss_drupal_timestamp)}</time>
                </div>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__stats">
                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('group', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">{this.props.translations.members}</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_group_statistic_members || 0}</span>
                </div>
                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">{this.props.translations.reactions}</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_group_statistic_comments || 0}</span>
                </div>
                <div className="ecl-teaser__stat">
                  <div dangerouslySetInnerHTML={{__html: svg('documents', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
                  <span className="ecl-teaser__stat-label">{this.props.translations.documents}</span>
                  <span className="ecl-teaser__stat-value">{this.props.result.its_group_statistic_files || 0}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default GroupResultItem;
