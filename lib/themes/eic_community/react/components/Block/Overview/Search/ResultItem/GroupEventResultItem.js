//TODO: stats is static because is not in the result

import React from 'react';
import AccessTimeIcon from '@material-ui/icons/AccessTime';
import StatisticsFooterResult from "./Partials/StatisticsFooterResult";
const svg = require('../../../../../svg/svg')

const GroupEventResultItem = ({result}) => {
  const startDate = new Date(result?.ds_content_field_date_range)
  const endDate = new Date(result?.ds_content_field_date_range_end_value)
  const now = Date.now()
  const isPastEvent = startDate.getTime() < now
  const isCurrentEvent = isPastEvent && endDate.getTime() > now

  const getLatestActivity = (updateDate) => {
    const uDate = new Date(updateDate).getTime()
    const diffTime = Math.abs(now - uDate)
    const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    return days > 7 ? `${days / 7} weeks` : `${days} days`
  }

  return (
    <div  className={"ecl-teaser-overview__item"}>
      <div className="ecl-teaser ecl-teaser--event  ecl-teaser--as-grey">
        <div className="ecl-teaser__main">
          <figure className="ecl-teaser__image-wrapper">
            <img className="ecl-teaser__image" src="https://picsum.photos/160/160" alt="" />
          </figure>
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <div className="ecl-teaser__tags-wrapper">
                  <div className="ecl-teaser__tags">
                    {
                      result?.sm_content_field_vocab_topics_string?.map((topic, index) => (
                        <div className="ecl-teaser__tag" key={index}>
                          <span className="ecl-tag ecl-tag--display">{topic}</span>
                        </div>
                      ))
                    }
                  </div>
                </div>
                <h2 className="ecl-teaser__title">
                  <a href={result?.ss_url} className="ecl-teaser__title-overflow"><span>{result?.tm_X3b_en_content_title_fulltext[0]}</span></a>
                </h2>
              </div>
              <div className="ecl-teaser__content-aside">
                <time className={`ecl-date-block ${isPastEvent ? "ecl-date-block--is-past" : ""} ${isCurrentEvent ? "ecl-date-block--is-current" : ""}` } dateTime={result.ds_content_field_date_range}>
                  <span className="ecl-u-sr-only">{result.ds_content_field_date_range}</span>
                  <span className="ecl-date-block__day" aria-hidden="true">{startDate.getDate()}</span>
                  <span className="ecl-date-block__month">{startDate.toLocaleString('default', { month: 'long' })}</span>
                  <span className="ecl-date-block__year" aria-hidden="true">{startDate.getFullYear()}</span>
                </time>
              </div>
            </div>
            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__updateTime">
                <AccessTimeIcon />
                Last activity {getLatestActivity(result.ds_aggregated_changed)} ago
              </div>
            </div>
          </div>
        </div>
        <div className="ecl-teaser__footer">
          <div className="ecl-teaser__stats-wrapper">
            <StatisticsFooterResult result={result}>
              <div className="ecl-teaser__stat">
                <div className="ecl-teaser__stat-icon" dangerouslySetInnerHTML={{__html: svg('remote', 'ecl-icon ecl-icon--grey ecl-icon--xs ecl-link__icon')}} />
                <span className="ecl-teaser__stat-label">Remote</span>
                <span className="ecl-teaser__stat-value">Remote event</span>
              </div>
              {
                result?.sm_content_field_vocab_geo_string[0] &&
                <div className="ecl-teaser__stat">
                  <div className="ecl-teaser__stat-icon" dangerouslySetInnerHTML={{__html: svg('mappin', 'ecl-icon ecl-icon--grey ecl-icon--xs ecl-link__icon')}} />
                  <span className="ecl-teaser__stat-label">Views</span>
                  <span className="ecl-teaser__stat-value">{result?.sm_content_field_vocab_geo_string[0]}</span>
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
