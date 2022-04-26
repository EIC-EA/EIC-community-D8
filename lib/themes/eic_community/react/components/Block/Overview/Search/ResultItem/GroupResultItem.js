import React from 'react';

const svg = require('../../../../../svg/svg');
import {timeDifferenceFromNow} from '../../../../../Services/TimeHelper';
import UserImage from '../../../../Utils/UserImage';
import {url} from "../../../../../Services/UrlHelper";

class GroupResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return;
    }

    console.log(this.props.result)
    const moderationState = this.props.result.ss_global_last_moderation_state;
    const groupImageFallback =
      this.props.type === 'organisation' ? 'organisation_circle' : 'group_circle';
    const itemClassName =
      this.props.type === 'organisation'
        ? 'ecl-teaser-overview__item ecl-teaser-overview__item--organisation'
        : 'ecl-teaser-overview__item';
    const itemUrl = url(this.props.result.ss_url);

    let tags = [
      {
        label: this.props.result.ss_group_visibility_label,
        id: this.props.result.ss_group_visibility_label && this.props.result.ss_group_visibility_label.toLowerCase(),
      },
    ];

    if (moderationState !== 'published') {
      let moderationId = 'public';
      switch(moderationState) {
        case 'draft':
        case 'blocked':
          moderationId = 'private';
          break;
        case 'pending':
          moderationId = 'restricted';
          break;
      }

      tags = [...tags, {
        label: moderationState.charAt(0).toUpperCase() + moderationState.slice(1),
        id: moderationId,
      }];
    }

    return (
      <div className={itemClassName} key={this.props.result.tm_global_title}>
        <div className="ecl-teaser ecl-teaser--group  ecl-teaser--as-card ecl-teaser--as-card-grey">
          <a href={itemUrl}>
            <figure className="ecl-teaser__image-wrapper">
              {this.props.result.ss_group_teaser_url_string !== undefined && (
                <img
                  className="ecl-teaser__image"
                  src={this.props.result.ss_group_teaser_url_string}
                  alt={this.props.result.tm_global_title}
                />
              )}
              {this.props.result.ss_group_teaser_url_string === undefined && (
                <div
                  className="ecl-teaser__image-fallback-wrapper"
                  dangerouslySetInnerHTML={{
                    __html: svg(groupImageFallback, 'ecl-icon ecl-icon--3xl'),
                  }}
                ></div>
              )}
            </figure>
          </a>

          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header">
              {!this.props.isAnonymous && tags.map((tag) => <div className={"ecl-editorial-header__tag"}><span className={`ecl-tag ecl-tag--display ecl-tag--is-${tag.id}`}>{tag.label}</span></div>)}
            </div>
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <h2 className="ecl-teaser__title">
                  <a href={itemUrl}>
                    <span className="ecl-teaser__title-overflow">
                      <span>{this.props.result.tm_global_title}</span>
                    </span>
                  </a>
                </h2>

                <div className="ecl-author ecl-author--is-tiny  ecl-teaser__meta">
                  <div className="ecl-author__information">
                    <span className="ecl-author__label ">
                      {!this.props.isAnonymous ? (
                        <a
                          href={this.props.result.ss_global_user_url}
                          className="ecl-link ecl-link--standalone ecl-author__link"
                        >
                          {this.props.result.ss_global_fullname}
                        </a>
                      ) : (
                        this.props.result.ss_global_fullname
                      )}
                    </span>
                  </div>
                  <div className="ecl-author__aside">
                    <UserImage
                      className={'ecl-author__media-wrapper'}
                      figureClassName={'ecl-author__media'}
                      imgClassName={'ecl-media-container__media'}
                      figureEmptyClassName={'ecl-author__media ecl-author__media--empty'}
                      src={this.props.result.ss_group_user_image}
                      alt={this.props.result.ss_global_fullname}
                      url={!this.props.isAnonymous ? this.props.result.ss_global_user_url : null}
                    />
                  </div>
                </div>

                <div className="ecl-timestamp ">
                  <span
                    dangerouslySetInnerHTML={{
                      __html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon'),
                    }}
                  />
                  <time className="ecl-timestamp__label">
                    Last activity{' '}
                    {timeDifferenceFromNow(this.props.result.ss_drupal_changed_timestamp)}
                  </time>
                </div>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__stats">
                <div className="ecl-teaser__stat">
                  <div
                    dangerouslySetInnerHTML={{
                      __html: svg('group', 'ecl-icon--xs ecl-teaser__stat-icon'),
                    }}
                  />
                  <span className="ecl-teaser__stat-label">{this.props.translations.members}</span>
                  <span className="ecl-teaser__stat-value">
                    {this.props.result.its_group_statistic_members || 0}
                  </span>
                </div>
                <div className="ecl-teaser__stat">
                  <div
                    dangerouslySetInnerHTML={{
                      __html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon'),
                    }}
                  />
                  <span className="ecl-teaser__stat-label">
                    {this.props.translations.reactions}
                  </span>
                  <span className="ecl-teaser__stat-value">
                    {this.props.result.its_group_statistic_comments || 0}
                  </span>
                </div>
                <div className="ecl-teaser__stat">
                  <div
                    dangerouslySetInnerHTML={{
                      __html: svg('documents', 'ecl-icon--xs ecl-teaser__stat-icon'),
                    }}
                  />
                  <span className="ecl-teaser__stat-label">
                    {this.props.translations.documents}
                  </span>
                  <span className="ecl-teaser__stat-value">
                    {this.props.result.its_group_statistic_files || 0}
                  </span>
                </div>
                <div className="ecl-teaser__stat">
                  <div
                    dangerouslySetInnerHTML={{
                      __html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon'),
                    }}
                  />
                  <span className="ecl-teaser__stat-label">{this.props.translations.like}</span>
                  <span className="ecl-teaser__stat-value">
                    {this.props.result.its_flag_recommend_group || 0}
                  </span>
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
