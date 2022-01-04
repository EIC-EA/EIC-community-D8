import React from "react";
import svg from "../../../../../svg/svg";


const Author = (props) => {
  return (
    <div className="ecl-comment__author">
      <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
        <div className="ecl-author__information">
          <span className="ecl-author__label ">
            <a
              href={props.url}
              className="ecl-link ecl-link--standalone ecl-author__link"
            >
              {props.fullname}
            </a>
          </span>
        </div>
        <Avatar {...props} />
      </div>
    </div>
  );
};

export const Avatar = ({avatar, fullname, url}) => (
  <div className="ecl-author__aside">
    <a
      href={url}
      className="ecl-author__media-wrapper"
    >
      {
        avatar
          ?
          <figure className="ecl-media-container ecl-author__media">
            <img
              alt={fullname}
              className="ecl-media-container__media"
              src={avatar}
            />
          </figure>
          :
          <figure
            className="ecl-media-container ecl-author__media"
            dangerouslySetInnerHTML={{__html: svg('user', 'ecl-icon--m')}}
          />
      }
    </a>
  </div>
)

export default React.memo(Author);
