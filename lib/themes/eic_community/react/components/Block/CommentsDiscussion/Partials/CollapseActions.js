import React from 'react';
import PopupForm from "./Popup";
import PopupRequestForm from "./PopupRequest";

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
            <PopupForm title={'Edit comment'} updateComment={props.updateComment} parentId={props.parentId} comment={props.comment} />
            </div>
          <div className="ecl-collapsible-options__item">
            <a
              onClick={(e) => props.flagComment(props.comment.comment_id, 'flag', 'request_delete_comment')}
              className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ecl-comment__delete"
              data-comment-id="1">Delete</a></div>

          <div className="ecl-collapsible-options__item">
            <PopupRequestForm title={'Request deletion'} flagComment={props.flagComment} type='request_delete_comment' comment={props.comment} />
          </div>
          <div className="ecl-collapsible-options__item">
            <PopupRequestForm title={'Request archival'} flagComment={props.flagComment} type='request_archive_comment' comment={props.comment} />
          </div>
        </div>
      </div>
    </div>
  </div>
}

export default CollapseActions;
