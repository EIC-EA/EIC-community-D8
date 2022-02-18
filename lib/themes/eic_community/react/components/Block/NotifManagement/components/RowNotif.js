import React, {useState, useEffect} from "react";
import svg from "../../../../svg/svg";
import classNames from "classnames";
import axios from "axios";
import {store} from 'react-notifications-component';

class NotifRow extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      showChildren: false,
      row: this.props.row
    };

    this.setShowChildren = this.setShowChildren.bind(this);
  }
  setShowChildren(state) {
    this.setState({
      showChildren: state
    });
  }
  render() {
    return (
      <>
        <Row translations={this.props.translations} parentIndex={this.props.rowIndex} row={this.props.row} showChildren={this.state.showChildren} setShowChildren={this.setShowChildren} unsubscribe={this.props.unsubscribe} showUnsubscribeModal={this.props.showUnsubscribeModal}/>
        {this.state.showChildren && this.state.row?.items?.map((item, index) => <Row translations={this.props.translations} parentIndex={this.props.rowIndex} rowIndex={index} row={item} isChild={true} unsubscribe={this.props.unsubscribe} key={index} showUnsubscribeModal={this.props.showUnsubscribeModal} />)}
      </>
    )
  }
}

class Row extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      toggle: Boolean(this.props.row.state),
    };
    this.handleToggleChange = this.handleToggleChange.bind(this);
    this.addNotification = this.addNotification.bind(this);

  }
  addNotification(message, level = 'success') {
    store.addNotification({
      message,
      type: level,
      insert: "top",
      container: "bottom-right",
      dismiss: {
        duration: 5000,
        onScreen: true
      },
      animationIn: ["animate__animated", "animate__fadeIn"],
      animationOut: ["animate__animated", "animate__fadeOut"],
    });
  }
  handleToggleChange() {
    const self = this;

    const toggleState = !this.state.toggle;
    this.setState({toggle: !this.state.toggle});
    // @TODO: implement ajax call
    /*axios.post(`${window.drupalSettings.path.baseUrl}toggle/${this.props.row.id}`,
      {state:toggleState, withCredentials: true})
      .then(response => {
        if(!(response.data && response.data.result)) {
          self.setState({toggle: !toggleState});
          self.addNotification(response.data.message, 'danger')
        }
      })
      .catch(function (error) {
        self.setState({toggle: !toggleState});
        self.addNotification('An error occurred', 'danger')
      })*/
  }

  render() {
    const asChild = !!this.props.row.items
    return (
      <>
        <tr onClick={() => !this.props.isChild ? this.props.setShowChildren(!this.props.showChildren) : false } className={classNames('ecl-my-notifications-management__table__item', {
          'ecl-my-notifications-management__table__item--as-no-child': !asChild && !this.props.isChild,
          'ecl-my-notifications-management__table__item--parent': !this.props.isChild && asChild
        })}>
          <td className="ecl-my-notifications-management__table__label">
            <div dangerouslySetInnerHTML={
              {__html: `${asChild || this.props.isChild ? svg(!this.props.isChild ? (this.props.showChildren ? 'arrow-down' : 'arrow') : 'child-arrow', ' ecl-icon--xs') : ''} <span>${this.props.row.name.label}</span>`}
            }/>
          </td>
          <td className="ecl-my-notifications-management__table__status">
            <div>
              <label className="ecl-toggle">
                <input type="checkbox" onChange={this.handleToggleChange} checked={this.state.toggle} name={this.props.row.name.label} />
                <div className="ecl-toggle__button"/>
                <span className="ecl-toggle__label"><span>{this.props.translations.toggle_off}</span> <span>{this.props.translations.toggle_on}</span></span>
              </label>
            </div>
          </td>
          {this.props.unsubscribe &&
            <td className="ecl-my-notifications-management__table__unsubscribe">
              <button onClick={(e) => {e.preventDefault(); e.stopPropagation(); this.props.showUnsubscribeModal(this.props.row.id, this.props.row.name.label, this.props.parentIndex, this.props.rowIndex); }}>{this.props.translations.notification_unsubscribe}</button>
            </td>
          }
        </tr>
      </>
    )
  }
}

export default NotifRow;
