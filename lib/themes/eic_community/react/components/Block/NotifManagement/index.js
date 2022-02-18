import React, {useState} from 'react'
import svg from "../../../svg/svg";
import axios from "axios";
import CircularProgress from "@material-ui/core/CircularProgress";
import NotifRow from "./components/RowNotif";
import "react-notifications-component/dist/theme.css";
import {store} from "react-notifications-component";

class Overview extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      urlApi: this.props.url,
      loading: false,
      results: this.props.data.items,
      title: this.props.data.title,
      searchText: '',
      currentItemToUnsubscribe: '',
      currentItemToUnsubscribeName: '',
      currentItemToUnsubscribeIndex: {parent: null, child: null},
      showUnsubscribeModal: false,
      translations: this.props.translations || {
        'unsubscribe_modal_close': 'Close',
        'unsubscribe_modal_title' : 'Modal title',
        'unsubscribe_modal_desc': 'Modal desc',
        'unsubscribe_modal_confirm': 'Confirm',
        'unsubscribe_modal_cancel': 'Cancel',
        'search_placeholder': 'Search',
        'notification_name': 'Name',
        'notification_status': 'Status',
        'notification_unsubscribe': 'Unsubscribe',
        'toggle_on': 'On',
        'toggle_off': 'Off'
      },
      initiated: false
    }
    this.updateSearchText = this.updateSearchText.bind(this);
    this.showUnsubscribeModal = this.showUnsubscribeModal.bind(this);
    this.unsubscribeConfirm = this.unsubscribeConfirm.bind(this);
    this.addNotification = this.addNotification.bind(this);

  }
  componentDidMount() {
    this.search();
  }

  updateSearchText(searchText) {
    this.setState({
      searchText
    })

    clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.search();
    }, 500);
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
  search() {
    const self = this;
    if (this.state.urlApi) {
      this.setState({
        loading: true
      });
      // add search params
      let params = {};
      axios.get(this.state.urlApi, {
        params,
        withCredentials: true,
      })
        .then(function (response) {
          self.setState({
            results: response.data.response,
            loading: false,
            initiated: true
          })
        }.bind(this))
        .catch(function (error) {
          self.setState({
            loading: false
          });
        });
    }
  }

  showUnsubscribeModal(id, name, parentIndex, rowIndex) {
    this.setState({
      currentItemToUnsubscribe: id,
      currentItemToUnsubscribeName: name,
      showUnsubscribeModal: true,
      currentItemToUnsubscribeIndex: {
        parent: parentIndex,
        child: rowIndex
      }
    });
  }

  unsubscribeConfirm() {
    this.setState({
      loading: true
    });

    // make call
    // @TODO: add actual URL
    this.setState({
      showUnsubscribeModal: false,
      loading: false
    });
    /*axios.get('/unsubscribe/' + this.state.currentItemToUnsubscribe,
      {withCredentials: true})
      .then(function (response) {
        let items = this.state.results;
        let parentIndex = this.state.currentItemToUnsubscribeIndex.parent;
        let childIndex = this.state.currentItemToUnsubscribeIndex.child;
        if (childIndex !== null && childIndex !== undefined) {
          items[parentIndex].items.splice(childIndex, 1);
          items[parentIndex].childCount -= 1;
        } else {
          items.splice(parentIndex, 1);
        }

        this.setState({
          showUnsubscribeModal: false,
          loading: false,
          results: items
        });
      }.bind(this))
      .catch(function (error) {
        self.props.addNotification('An error occured', 'error')
        this.setState({
           showUnsubscribeModal: false,
           loading: false
         });
      }.bind(this));*/
  }

  render() {
    return (
      <>
        <div className="ecl-my-notifications-management__header">
          <h2 className="ecl-notifications-management__title">{this.props.data.title}</h2>
          <div className="ecl-subnavigation__searchform-wrapper">
            <form className="ecl-search-form ecl-subnavigation__searchform" role="search">
              <div className="ecl-form-group">
                <input onChange={this.updateSearchText} id="input-search" className="ecl-text-input ecl-text-input--m ecl-search-form__text-input"
                       name="search" type="search" placeholder={this.state.translations.search_placeholder} />
              </div>
              <button className="ecl-button ecl-button--search ecl-search-form__button" type="submit" aria-label="Search">
          <span className="ecl-button__container" dangerouslySetInnerHTML={{__html: `<span class="ecl-button__label" data-ecl-label="true">${this.state.translations.search_placeholder}</span> ${svg('search', ' ecl-icon--xs ecl-button__icon ecl-button__icon--after')}`}}>
          </span>
              </button>
            </form>
          </div>
        </div>
        {this.state.loading && <CircularProgress style={{top: '50%', left: '50%', position: 'fixed', zIndex: '51'}} className="loader" size={50} />}
        <table className="ecl-my-notifications-management__table" cellPadding="0" cellSpacing="0">
          <thead>
          <tr>
            <th>{this.state.translations.notification_name}</th>
            <th>{this.state.translations.notification_status}</th>
            {this.props.unsubscribe &&
              <th>{this.state.translations.notification_unsubscribe}</th>
            }
          </tr>
          </thead>
          <tbody>
          {this.state.results.map((row, index) => {
            return (
              <NotifRow rowIndex={index} row={row} key={index} unsubscribe={this.props.unsubscribe} showUnsubscribeModal={this.showUnsubscribeModal} translations={this.state.translations} />
            )
          })}
          </tbody>
        </table>

        <div className={`ecl-viewport__modal ${!this.state.showUnsubscribeModal && 'modal-hide'}`}>
          <div className="ecl-viewport__modal__content">
          <span
            onClick={() => this.setState({showUnsubscribeModal: false})}
            style={{cursor: 'pointer'}}
            className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost"
          >
            <span className="ecl-link__label">{this.state.translations.unsubscribe_modal_close}</span>&nbsp;
            <svg className="ecl-icon ecl-icon--2xs ecl-link__icon" focusable="false" aria-hidden="true">
            </svg>
          </span>    <h3 className="ecl-activity-stream__modal__title">{this.state.translations.unsubscribe_modal_title}</h3>
            <p className="ecl-activity-stream__modal__description">
              {this.state.translations.unsubscribe_modal_desc}
            </p>
            <div className="ecl-inline-actions">
              <div className="ecl-inline-actions__items">
                <div className="ecl-inline-actions__item">
                <span
                  onClick={() => this.unsubscribeConfirm()}
                  style={{cursor: 'pointer'}}
                  className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary">
                  {this.state.translations.unsubscribe_modal_confirm}
                </span>
                </div>
                <div className="ecl-inline-actions__item">
                <span
                  onClick={() => this.setState({showUnsubscribeModal: false})}
                  style={{cursor: 'pointer'}}
                  className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost"
                >
                  {this.state.translations.unsubscribe_modal_cancel}
                </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </>
    )
  }
}

export default Overview;
