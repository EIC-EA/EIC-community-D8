import React from 'react';
import DeleteActivityStream from "../DeleteActivityStream";
import Footer from "../../Overview/Search/ResultItem/ActivityStream/Footer";
import svg from "../../../../svg/svg";

const ResultItemWrapper = (props) => {
  const fullname = `${props.result.ss_author_first_name} ${props.result.ss_author_last_name}`;
  return <div className="ecl-activity-stream__item">
    <DeleteActivityStream
      showDeleteModal={props.showDeleteModal}
      messageId={props.result.its_message_id}
    />
    <div className="ecl-activity-stream__item__main">
      <div className="ecl-activity-stream__item__image-wrapper">
        <div className="ecl-author ecl-author--is-medium  ecl-author--is-minimal">
          <div className="ecl-author__information">
            <span className="ecl-author__label ">{fullname}</span>
          </div>
          <div className="ecl-author__aside">
            <AuthorMediaWrapper isAnonymous={props.isAnonymous} url={props.result.ss_global_user_url}>
              {!props.isAnonymous && props.result.ss_author_formatted_profile_picture ?
                <figure className="ecl-media-container ecl-author__media">
                  <img alt={fullname}
                       className="ecl-media-container__media"
                       src={props.result.ss_author_formatted_profile_picture}
                  />
                </figure>
              :
                <figure className="ecl-media-container ecl-author__media" dangerouslySetInnerHTML={{__html: svg('user', 'ecl-icon')}} />
              }
            </AuthorMediaWrapper>
          </div>
        </div>
      </div>

      <div className="ecl-activity-stream__item__content">
        <div className="ecl-activity-stream__item__type">
          {props.children}
        </div>
      </div>
    </div>
    <Footer result={props.result}/>
  </div>
}

const AuthorMediaWrapper = ({isAnonymous, url, children}) => {
  return !isAnonymous
    ? <a href={url} className="ecl-author__media-wrapper">{children}</a>
    : <div className="ecl-author__media-wrapper">{children}</div>
}

export default ResultItemWrapper;
