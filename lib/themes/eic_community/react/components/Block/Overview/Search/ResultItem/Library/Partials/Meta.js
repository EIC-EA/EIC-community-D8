import svg from "../../../../../../../svg/svg";
import {timeDifferenceFromNow} from "../../../../../../../Services/TimeHelper";
import React from "react";


const Meta = (props) => {
  return (
    <div className="ecl-teaser__meta-content">
      <div className="ecl-teaser__meta-content-item">
        <div className="ecl-timestamp ">
          <span
            dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
          />
          <time className="ecl-timestamp__label">{timeDifferenceFromNow(props.result.ss_drupal_timestamp)}</time>
        </div>
      </div>
      <div className="ecl-teaser__meta-content-item">
        <span
          dangerouslySetInnerHTML={{__html: svg('document', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
        />
        {props.result.ss_content_language_string}
      </div>
      <div className="ecl-teaser__meta-content-item">
                <span
                  dangerouslySetInnerHTML={{__html: svg('remote', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                />
        {!props.isAnonymous ? (
          <a href={props.result.ss_global_user_url}>{props.translations.uploaded_by} {props.result.ss_global_fullname}</a>
        ) : (
          <span>{props.translations.uploaded_by} {props.result.ss_global_fullname}</span>
        )}
      </div>
    </div>
  )
}

export default Meta
