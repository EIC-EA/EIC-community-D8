//TODO: stats is static because is not in the result

import React from 'react';
import AccessTimeIcon from '@material-ui/icons/AccessTime';
import StatisticsFooterResult from "./Partials/StatisticsFooterResult";
import {timeDifferenceFromNow} from "../../../../../Services/TimeHelper";
import {url} from "../../../../../Services/UrlHelper";

const svg = require('../../../../../svg/svg')

const GroupEventResultItem = ({result}) => {
  const startDate = new Date(result?.ds_content_field_date_range)
  const endDate = new Date(result?.ds_content_field_date_range_end_value)
  const startDateMonth = startDate.toLocaleString('default', {month: 'short'})
  const endDateMonth = endDate.toLocaleString('default', {month: 'short'})
  const startDateYear = startDate.getFullYear()
  const endDateYear = endDate.getFullYear()
  const now = Date.now()
  const isPastEvent = startDate.getTime() < now
  const isCurrentEvent = isPastEvent && endDate.getTime() > now
  const groupImageFallback = '<use xlink:href="themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#custom--group_circle"></use>';
  const hasTeaser = result.ss_content_image_url !== undefined;
  const itemUrl = result.ss_url ? url(result.ss_url) : ''

  return (
    <div  className={"ecl-teaser-overview__item "}>
      <div className="ecl-teaser ecl-teaser--event ecl-teaser--is-card">
        <div className="ecl-teaser__main">
          <a href={itemUrl}>
            <figure className={`ecl-teaser__image-wrapper ${!hasTeaser ? 'ecl-teaser__image-wrapper--empty' : ''}`}>
              {result.ss_content_image_url !== undefined &&
              <img className="ecl-teaser__image" src={result?.ss_event_formatted_image} alt=""/>
              }
              {result.ss_content_image_url === undefined &&
              <div className="ecl-teaser__image-fallback-wrapper">
                <svg className="ecl-icon ecl-icon--3xl" focusable="false" aria-hidden="true" dangerouslySetInnerHTML={{__html: groupImageFallback }} />
              </div>
              }
            </figure>
          </a>
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <div className="ecl-teaser__tags-wrapper">
                  <div className="ecl-teaser__tags">
                    <div className="ecl-teaser__tag">
                      <span className="ecl-tag ecl-tag--display">{result?.ss_content_event_type_string}</span>
                    </div>
                  </div>
                </div>
                <h2 className="ecl-teaser__title">
                  <a href={itemUrl} className="ecl-teaser__title-overflow"><span>{result?.tm_X3b_en_content_title_fulltext[0]}</span></a>
                </h2>
              </div>
              <div className="ecl-teaser__content-aside">
                <time className={`ecl-date-block ${isPastEvent ? "ecl-date-block--is-past" : ""} ${isCurrentEvent ? "ecl-date-block--is-current" : ""}` } dateTime={result.ds_content_field_date_range}>
                  <span className="ecl-u-sr-only">{result.ds_content_field_date_range}</span>
                  <span className="ecl-date-block__day" aria-hidden="true">{startDate.getDate() === endDate.getDate() ? startDate.getDate() : `${startDate.getDate()}-${endDate.getDate()}`}</span>
                  <span className="ecl-date-block__month">{startDateMonth === endDateMonth ? startDateMonth : `${startDateMonth}-${endDateMonth}`}</span>
                  <span className="ecl-date-block__year" aria-hidden="true">{startDateYear === endDateYear ? startDateYear : `${startDateYear}-${endDateYear}`}</span>
                </time>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__updateTime">
                <AccessTimeIcon />
                Last activity {timeDifferenceFromNow(Date.parse(result.ds_aggregated_changed) / 1000)}
              </div>
            </div>
          </div>
        </div>
        <div className="ecl-teaser__footer">
          <div className="ecl-teaser__stats-wrapper">
            <StatisticsFooterResult result={result}>
              {
                result.sm_content_field_location_type && result.sm_content_field_location_type.includes('remote') !== false &&
                <div className="ecl-teaser__stat">
                  <div className="ecl-teaser__stat-icon"
                       dangerouslySetInnerHTML={{__html: svg('remote', 'ecl-icon ecl-icon--grey ecl-icon--xs ecl-link__icon')}}/>
                  <span className="ecl-teaser__stat-label">Remote</span>
                  <span className="ecl-teaser__stat-value">Remote event</span>
                </div>
              }
              {
                result?.ss_content_country_code && result?.ss_content_event_locality && result.sm_content_field_location_type.includes('on_site') !== false &&
                <div className="ecl-teaser__stat">
                  <div className="ecl-teaser__stat-icon"
                       dangerouslySetInnerHTML={{__html: svg('mappin', 'ecl-icon ecl-icon--grey ecl-icon--xs ecl-link__icon')}}/>
                  <span className="ecl-teaser__stat-label">On site</span>
                  <span className="ecl-teaser__stat-value">{`${result?.ss_content_country_code}, ${result?.ss_content_event_locality}`}</span>
                </div>
              }
            </StatisticsFooterResult>
          </div>
        </div>
      </div>
    </div>
  )
}

export default GroupEventResultItem;
