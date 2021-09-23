import React, {useEffect} from 'react';
import PopupForm from "./Popup";
import PopupRequestForm from "./PopupRequest";
import initCollapse from "../../../../Services/CollapseOptions";
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";
import {canShowActions, hasPermission, hasPermissionApi} from "../Services/Permissions";

class CollapseActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canRequestDelete: false,
      canRequestArchive: false,
      canShowIconActions: false,
      isOwnerComment: parseInt(props.comment.user_id) === parseInt(window.drupalSettings.user.uid)
    }
  }

  componentDidMount() {
    const self = this;

    hasPermissionApi(this.props.comment, 'request_delete_comment').then(hasPermission => {
      self.setState(
        {canRequestDelete: hasPermission},
        () => hasPermissionApi(this.props.comment, 'request_archive_comment').then(hasPermission => {
          self.setState(
            {canRequestArchive: hasPermission},
            self.setActionsPermission
          );
        })
      );
    })
  }

  setActionsPermission() {
    this.setState({
      canShowIconActions: canShowActions(this.props.comment) || this.state.canRequestDelete || this.state.canRequestArchive,
    }, initCollapse)
  }

  render() {
    return <div className={`ecl-collapsible-options ecl-collapsible-options--aligns-from-right`}>
      <div className="ecl-collapsible-options__trigger-wrapper">
        <button
          style={{display: this.state.canShowIconActions ? 'flex' : 'none'}}
          className={`ecl-button ecl-button--ghost ecl-button--as-form-option ecl-collapsible-options__trigger`}
          type="submit"
          tabIndex="-1"
        ><span className="ecl-button__container"><span
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
            {(hasPermission('edit_all_comments') || (hasPermission('edit_own_comments') && this.state.isOwnerComment)) &&
            <div className="ecl-collapsible-options__item">
              <PopupForm title={getTranslation('action_edit_comment')} actionComment={this.props.updateComment}
                         parentId={this.props.parentId} showTextArea={true} comment={this.props.comment}/>
            </div>
            }

            {(this.state.isOwnerComment || hasPermission('delete_all_comments')) &&
            <div className="ecl-collapsible-options__item">
              <PopupForm title={getTranslation('action_delete_comment')} actionComment={this.props.deleteComment}
                         parentId={this.props.parentId} showTextArea={false} comment={this.props.comment}/>
            </div>
            }

            {(this.state.canRequestDelete && !this.state.isOwnerComment) &&
            <div className="ecl-collapsible-options__item">
              <PopupRequestForm title={getTranslation('action_request_delete')} flagComment={this.props.flagComment}
                                type='request_delete_comment' comment={this.props.comment}/>
            </div>
            }
            {(this.state.canRequestArchive && !this.state.isOwnerComment) &&
            <div className="ecl-collapsible-options__item">
              <PopupRequestForm title={getTranslation('action_request_archival')} flagComment={this.props.flagComment}
                                type='request_archive_comment' comment={this.props.comment}/>
            </div>
            }
          </div>
        </div>
      </div>
    </div>
  }
}

export default CollapseActions;
