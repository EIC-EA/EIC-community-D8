import React, { useState, useEffect } from 'react';
import svg from '../../../../svg/svg';
import classNames from 'classnames';
import axios from 'axios';
import { store } from 'react-notifications-component';
import Toggle from '../../Toggle/index';

class NotifRow extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showChildren: false,
      row: this.props.row,
      hidden: this.props.hidden || false,
    };

    this.setShowChildren = this.setShowChildren.bind(this);
  }
  setShowChildren(state) {
    this.setState({
      showChildren: state,
    });
  }
  render() {
    return (
      <>
        {this.props.hidden !== true && (
          <Row
            translations={this.props.translations}
            parentIndex={this.props.rowIndex}
            row={this.props.row}
            showChildren={this.state.showChildren}
            setShowChildren={this.setShowChildren}
            unsubscribe={this.props.unsubscribe}
            showUnsubscribeModal={this.props.showUnsubscribeModal}
          />
        )}
        {this.props.hidden !== true &&
          this.state.showChildren &&
          this.state.row?.items?.map((item, index) => (
            <Row
              translations={this.props.translations}
              parentIndex={this.props.rowIndex}
              rowIndex={index}
              row={item}
              isChild={true}
              unsubscribe={this.props.unsubscribe}
              key={index}
              showUnsubscribeModal={this.props.showUnsubscribeModal}
            />
          ))}
      </>
    );
  }
}

class Row extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      toggle: Boolean(this.props.row.state),
    };
    this.addNotification = this.addNotification.bind(this);
    this.handleToggleChange = this.handleToggleChange.bind(this);
  }
  addNotification(message, level = 'success') {
    store.addNotification({
      message,
      type: level,
      insert: 'top',
      container: 'bottom-right',
      dismiss: {
        duration: 5000,
        onScreen: true,
      },
      animationIn: ['animate__animated', 'animate__fadeIn'],
      animationOut: ['animate__animated', 'animate__fadeOut'],
    });
  }
  handleToggleChange(toggleState) {
    const self = this;
    this.props.row.state = toggleState;
  }

  render() {
    const asChild = !!this.props.row.items;
    return (
      <>
        <tr
          onClick={() =>
            !this.props.isChild ? this.props.setShowChildren(!this.props.showChildren) : false
          }
          className={classNames('ecl-my-notifications-management__table__item', {
            'ecl-my-notifications-management__table__item--parent': !this.props.isChild && asChild,
          })}
        >
          <td className="ecl-my-notifications-management__table__label">
            <div
              dangerouslySetInnerHTML={{
                __html: `${
                  asChild || this.props.isChild
                    ? svg(
                        !this.props.isChild
                          ? this.props.showChildren
                            ? 'arrow-down'
                            : 'arrow'
                          : 'child-arrow',
                        ' ecl-icon--xs'
                      )
                    : ''
                } <a href='${this.props.row.name.path}'>${this.props.row.name.label}</a>`,
              }}
            />
          </td>
          <td className="ecl-my-notifications-management__table__status">
            <div>
              <Toggle
                url={this.props.row.update_url}
                checked={this.state.toggle}
                name={this.props.row.name.label}
                translations={this.props.translations}
                toggled={this.handleToggleChange}
              />
            </div>
          </td>
          {this.props.unsubscribe && (
            <td className="ecl-my-notifications-management__table__unsubscribe">
              <button
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  this.props.showUnsubscribeModal(
                    this.props.row.id,
                    this.props.row.name.label,
                    this.props.parentIndex,
                    this.props.rowIndex,
                    this.props.row.unsubscribe_url
                  );
                }}
              >
                {this.props.translations.notification_unsubscribe}
              </button>
            </td>
          )}
        </tr>
      </>
    );
  }
}

export default NotifRow;
