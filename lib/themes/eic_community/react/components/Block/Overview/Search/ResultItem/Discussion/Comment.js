import React from 'react';
import {timeDifferenceFromNow} from "../../../../../../Services/TimeHelper";
import UserImage from "../../../../../Utils/UserImage";

const svg = require('../../../../../../svg/svg')

const Comment = (props) => {
  return <div className="ecl-comment-thread ecl-discussion-thread__comment-thread ">

    <div className="ecl-comment-thread ">
      <div className="ecl-comment ecl-comment--is-reply ecl-comment-thread__comment" id="comment-001">
        <div className="ecl-comment__author">
          <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
            <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={props.comment.author_url}
                         className="ecl-link ecl-link--standalone ecl-author__link">{props.comment.author}</a>                  </span>
            </div>
            <div className="ecl-author__aside">
              <UserImage
                className={'ecl-author__media-wrapper'}
                figureClassName={'ecl-author__media'}
                imgClassName={'ecl-media-container__media'}
                figureEmptyClassName={'ecl-author__media ecl-author__media--empty'}
                url={props.comment.author_url}
                alt={props.comment.author}
                src={props.comment.author_image}
              />
            </div>
          </div>
        </div>

        <div className="ecl-comment__content">
          <header className="ecl-comment__header">
            <div className="ecl-comment__header-main">

              <div className="ecl-comment__author-info">
                <a href={props.comment.author_url}
                   className="ecl-link ecl-link--standalone ecl-comment__author-name">{props.comment.author}</a>
              </div>
              <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
                <span
                  dangerouslySetInnerHTML={{__html: svg('time', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}}
                />
                <time className="ecl-timestamp__label">{timeDifferenceFromNow(props.comment.time)}</time>
              </div>
            </div>


          </header>

          <div className="ecl-comment__main">
            <div className="ecl-editable-wrapper">
              {props.comment.text}
            </div>

          </div>

          <footer className="ecl-comment__footer">

            <div className="ecl-comment__stats-wrapper">
              <div className="ecl-comment__stats">
                <div className="ecl-comment__stat">
                  <span
                    dangerouslySetInnerHTML={{__html: svg('comment', 'ecl-icon ecl-icon--xs ecl-comment__stat-icon')}}
                  />
                  <span className="ecl-comment__stat-label">Likes</span>
                  <span className="ecl-comment__stat-value">20</span>
                </div>
              </div>
            </div>
          </footer>
        </div>

      </div>

    </div>
  </div>
}

export default Comment;
