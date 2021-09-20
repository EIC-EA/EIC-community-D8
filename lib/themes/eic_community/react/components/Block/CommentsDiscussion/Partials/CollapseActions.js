import React from 'react';
import PopupForm from "./Popup";
import PopupRequestForm from "./PopupRequest";

const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";

const CollapseActions = (props) => {
  return <div className="ecl-collapsible-options ecl-collapsible-options--aligns-from-right">
    <div className="ecl-collapsible-options__trigger-wrapper">
      <button className="ecl-button ecl-button--ghost ecl-button--as-form-option ecl-collapsible-options__trigger"
              type="submit"
              tabIndex="-1"><span className="ecl-button__container"><span
        className="ecl-button__label" data-ecl-label="true">{getTranslation('options')}</span>
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
            <PopupForm title={getTranslation('action_edit_comment')} actionComment={props.updateComment} parentId={props.parentId} showTextArea={true} comment={props.comment} />
            </div>
          <div className="ecl-collapsible-options__item">
            <PopupForm title={getTranslation('action_delete_comment')} actionComment={props.deleteComment} parentId={props.parentId} showTextArea={false} comment={props.comment} />
          </div>

          <div className="ecl-collapsible-options__item">
            <PopupRequestForm title={getTranslation('action_request_delete')} flagComment={props.flagComment} type='request_delete_comment' comment={props.comment} />
          </div>
          <div className="ecl-collapsible-options__item">
            <PopupRequestForm title={getTranslation('action_request_archival')} flagComment={props.flagComment} type='request_archive_comment' comment={props.comment} />
          </div>
        </div>
      </div>
    </div>
  </div>
}

export default CollapseActions;
