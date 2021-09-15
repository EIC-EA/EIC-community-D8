import React from 'react';
import PopupForm from "./Popup";

const svg = require('../../../../svg/svg')

const CollapseActions = (props) => {
  return <div className="ecl-collapsible-options ecl-collapsible-options--aligns-from-right">
    <div className="ecl-collapsible-options__trigger-wrapper">
      <button className="ecl-button ecl-button--ghost ecl-button--as-form-option ecl-collapsible-options__trigger"
              type="submit"
              tabIndex="-1"><span className="ecl-button__container"><span
        className="ecl-button__label" data-ecl-label="true">Options</span>
                                <span
                                  dangerouslySetInnerHTML={{__html: svg('ellipsis', 'ecl-icon ecl-icon--s ecl-icon--rotate-90 ecl-button__icon ecl-button__icon--after')}}
                                />
                                </span>
      </button>
    </div>

    <div className="ecl-collapsible-options__collapse-wrapper">
      <div className="ecl-collapsible-options__collapse-well">
        <div className="ecl-collapsible-options__items">
          <div className="ecl-collapsible-options__item">
            <PopupForm title={'Edit comment'} updateComment={props.updateComment} comment={props.comment} />
            </div>

          <div className="ecl-collapsible-options__item">
            <a href="/comment/1/delete?destination=/discussions/how-can-we-improve-mobility-antwerp"
               className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__delete"
               data-comment-id="1">Delete comment</a></div>
          <div className="ecl-collapsible-options__item">


            <a
              href="/request/delete/comment/1?destination=/discussions/how-can-we-improve-mobility-antwerp"
              className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__delete"
              data-comment-id="1">Request deletion</a></div>
          <div className="ecl-collapsible-options__item">


            <a
              href="/request/archive/comment/1?destination=/discussions/how-can-we-improve-mobility-antwerp"
              className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__delete"
              data-comment-id="1">Request archival</a></div>
        </div>
      </div>
    </div>
  </div>
}

export default CollapseActions;
