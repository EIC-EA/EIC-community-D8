import React from 'react';
import {getMonthByIndex} from "../../../../../Services/TimeHelper";
import {url} from "../../../../../Services/UrlHelper"
import UserImage from "../../../../Utils/UserImage";
const svg = require('../../../../../svg/svg')

export default function StoryResultItem(props) {
  const date = new Date(props.result.ss_global_created_date);
  const initials = props.result.ss_global_fullname.split(' ', 2);
  const itemUrl = url(props.result.ss_url)
  const visibilityClass = props.result.bs_is_restricted ? 'private' : 'public';
  const visibilityLabel = props.translations[visibilityClass];

  return <div className="ecl-teaser-overview__item ">

    <div className="ecl-teaser ecl-teaser--story  ecl-teaser--is-card">
      <a href={itemUrl}>
        <figure className="ecl-teaser__image-wrapper">
          <img className="ecl-teaser__image" src={props.result.ss_content_teaser_image_url} alt={props.result.tm_global_title} />
        </figure>
      </a>
      <div className="ecl-teaser__main-wrapper">
        <div className="ecl-teaser__meta-header">
          <div className="ecl-teaser__meta-column">
            <div className="ecl-content-type-indicator ecl-teaser__type">
              <div dangerouslySetInnerHTML={{__html: svg(props.result.ss_content_type, 'ecl-icon ecl-icon--m ecl-content-type-indicator__icon')}}/>
              <span className="ecl-content-type-indicator__label">{props.result.ss_content_type}</span>
            </div>

            <span className={`ecl-tag ecl-tag--display ecl-tag--is-${visibilityClass}`}>
              {visibilityLabel}
            </span>
          </div>

          <div className="ecl-teaser__meta-column ecl-teaser__meta-column--right">
            <div className="ecl-timestamp ">
              <span dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}} />
              <time className="ecl-timestamp__label">{`${date.getDate()} ${getMonthByIndex(date.getMonth())} ${date.getFullYear()}`}</time>
            </div>
          </div>
        </div>

        <div className="ecl-teaser__content">
          <h2 className="ecl-teaser__title">
            <a href={itemUrl}>
              <span className="ecl-teaser__title-overflow"><span>{props.result.tm_global_title}</span></span>
            </a>
          </h2>
          <div className="ecl-teaser__description" dangerouslySetInnerHTML={{__html: props.result?.tm_X3b_en_content_introduction_string?.join('')}} />
        </div>

        <div className="ecl-teaser__meta-footer">
          <div className="ecl-author ecl-author--is-default  ecl-teaser__author">
            <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      {props.result.ss_global_fullname}
                  </span>
            </div>
            <div className="ecl-author__aside">
              <UserImage
                className={'ecl-author__media-wrapper'}
                figureClassName={'ecl-author__media'}
                imgClassName={'ecl-media-container__media'}
                figureEmptyClassName={'ecl-author__media ecl-author__media--empty'}
                src={!props.isAnonymous ? url(props.result.ss_content_author_formatted_image) : null}
                alt={props.result.ss_global_fullname}
                url={!props.isAnonymous ? props.result.ss_global_user_url : null}
              />
            </div>
          </div>
          <div className="ecl-teaser__stats">
            <div className="ecl-teaser__stat">
              <div dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
              <span className="ecl-teaser__stat-label">Comments</span>
              <span className="ecl-teaser__stat-value">{props.result.its_content_comment_count}</span>
            </div>
            <div className="ecl-teaser__stat">
              <div dangerouslySetInnerHTML={{__html: svg('views', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
              <span className="ecl-teaser__stat-label">Views</span>
              <span className="ecl-teaser__stat-value">{props.result.its_statistics_view}</span>
            </div>
            <div className="ecl-teaser__stat">
              <div dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}/>
              <span className="ecl-teaser__stat-label">Likes</span>
              <span className="ecl-teaser__stat-value">{props.result.its_flag_like_content || 0}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
}
