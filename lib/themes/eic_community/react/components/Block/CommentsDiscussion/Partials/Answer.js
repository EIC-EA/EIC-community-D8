import React from 'react';
import timeDifferenceFromNow from "../../../../Services/TimeHelper";
const svg = require('../../../../svg/svg')

const Answer = (props) => {
  const child = props.child;

  return <div className="ecl-comment-thread">
    <div className="ecl-comment ecl-comment--is-reply ecl-comment-thread__comment" id={child.comment_id}>
      <div className="ecl-comment__author">
        <div className="ecl-author ecl-author--is-default  ecl-author--hide-info">
          <div className="ecl-author__information">
                <span className="ecl-author__label ">
                      <a href={child.user_url}
                         className="ecl-link ecl-link--standalone ecl-author__link">{child.user_fullname}</a>                  </span>
          </div>
          <div className="ecl-author__aside">
            <a href={child.user_url} className="ecl-author__media-wrapper">
              <figure className="ecl-media-container ecl-author__media"><img
                alt="Avatar image of Jane Doe" className="ecl-media-container__media"
                src={child.user_image} /></figure>
            </a>
          </div>
        </div>
      </div>

      <div className="ecl-comment__content">
        <header className="ecl-comment__header">
          <div className="ecl-comment__header-main">

            <div className="ecl-comment__author-info">
              <a href="?author=amFuZWRvZQ=="
                 className="ecl-link ecl-link--standalone ecl-comment__author-name">{child.user_fullname}</a>
              <span className="ecl-comment__origin">in reply to</span>
              <a href={`#${child.user_fullname}`}
                 className="ecl-link ecl-link--standalone ecl-comment__author-name">{child.user_fullname}</a></div>
            <div className="ecl-timestamp ecl-comment__timestamp ecl-timestamp--meta">
              <time className="ecl-timestamp__label">{timeDifferenceFromNow(child.created_timestamp)}</time>
            </div>
          </div>


        </header>

        <div className="ecl-comment__main">
          <div className="ecl-editable-wrapper">
            {child.text}
          </div>

        </div>

        <footer className="ecl-comment__footer">
          <div className="ecl-comment__actions-wrapper">
            <div className="ecl-comment__actions">
              <div className="ecl-comment__action">

              </div>
            </div>
          </div>


          <div className="ecl-comment__stats-wrapper">
            <div className="ecl-comment__stats">
              <div className="ecl-comment__stat">
                <a
                  style={{textDecoration: 'none', cursor: 'pointer'}}
                  dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon--xs ecl-teaser__stat-icon')}}
                  onClick={(e) => props.likeComment(child.comment_id, child.likes.hasAccountLike ? 'unflag' : 'flag')}
                />
                <span className="ecl-comment__stat-label">Likes</span>
                <span className="ecl-comment__stat-value">{child.likes.total}</span>
              </div>
            </div>
          </div>
        </footer>
      </div>

    </div>

  </div>
}

export default Answer;
