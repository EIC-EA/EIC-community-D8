import React, { useState } from 'react';
import svg from '../../../svg/svg';
import axios from 'axios';
import CircularProgress from '@material-ui/core/CircularProgress';
import NotifRow from './components/RowNotif';
import 'react-notifications-component/dist/theme.css';
import { store } from 'react-notifications-component';
import Pagination from '../Overview/Search/Pagination';
import { getParamsFromUrl } from '../../Utils/Url';

class Overview extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      urlApi: this.props.url,
      loading: true,
      results: this.props.items,
      title: this.props.title,
      searchText: '',
      currentItemToUnsubscribe: '',
      currentItemToUnsubscribeName: '',
      currentItemToUnsubscribeIndex: { parent: null, child: null },
      currentItemToUnsubscribeUrl: '',
      showUnsubscribeModal: false,
      translations: window.drupalSettings.translations || {
        unsubscribe_modal_close: 'Close',
        unsubscribe_modal_title: 'Modal title',
        unsubscribe_modal_desc: 'Modal desc',
        unsubscribe_modal_confirm: 'Confirm',
        unsubscribe_modal_cancel: 'Cancel',
        search_placeholder: 'Search',
        notification_name: 'Name',
        notification_status: 'Status',
        notification_unsubscribe: 'Unsubscribe',
        toggle_on: 'On',
        toggle_off: 'Off',
        no_results_title: 'No results',
        no_results_body: 'No results body goes here',
      },
      initiated: false,
      total: 0,
      resultsPerPage: 10,
      page: getParamsFromUrl('page') || 1,
      demo: this.props.demo || false,
    };
    this.updateSearchText = this.updateSearchText.bind(this);
    this.showUnsubscribeModal = this.showUnsubscribeModal.bind(this);
    this.unsubscribeConfirm = this.unsubscribeConfirm.bind(this);
    this.addNotification = this.addNotification.bind(this);
  }
  componentDidMount() {
    this.load();
  }

  updateSearchText(e) {
    this.setState({
      searchText: e.target.value,
    });
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
  load() {
    const self = this;
    if (this.state.urlApi) {
      this.setState({
        loading: true,
      });
      // add search params
      let params = {};
      axios
        .get(this.state.urlApi, {
          params,
          withCredentials: true,
        })
        .then(
          function (response) {
            self.setState({
              results: response.data,
              loading: false,
              initiated: true,
            });
          }.bind(this)
        )
        .catch(function (error) {
          self.setState({
            loading: false,
          });
        });
    }
  }
  search() {
    if (this.state.searchText.length > 0) {
      return this.state?.results?.items?.filter((item) => {
        return (
          (!item.hasOwnProperty('hidden') || item.hidden === false) &&
          item.name.label.toString().toLowerCase().indexOf(this.state.searchText.toLowerCase()) > -1
        );
      });
    } else {
      return this.state?.results?.items;
    }
  }

  showUnsubscribeModal(id, name, parentIndex, rowIndex, url) {
    this.setState({
      currentItemToUnsubscribe: id,
      currentItemToUnsubscribeName: name,
      showUnsubscribeModal: true,
      currentItemToUnsubscribeUrl: url,
      currentItemToUnsubscribeIndex: {
        parent: parentIndex,
        child: rowIndex,
      },
    });
  }

  unsubscribeConfirm(e) {
    this.setState({
      loading: true,
    });

    axios
      .post(this.state.currentItemToUnsubscribeUrl, { withCredentials: true })
      .then(
        function (response) {
          let items = this.state.results;
          let parentIndex = this.state.currentItemToUnsubscribeIndex.parent;
          let childIndex = this.state.currentItemToUnsubscribeIndex.child;
          if (childIndex !== null && childIndex !== undefined) {
            items[parentIndex].items.splice(childIndex, 1);
            items[parentIndex].childCount -= 1;
          } else {
            items.items = items.items.filter(
              (item) => item.id != this.state.currentItemToUnsubscribe
            );
          }

          this.setState({
            showUnsubscribeModal: false,
            currentItemToUnsubscribeUrl: '',
            loading: false,
            results: items,
          }, this.load);
        }.bind(this)
      )
      .catch(
        function (error) {
          console.log(error);
        }.bind(this)
      );
  }

  render() {
    if (this.state.loading) return <></>;
    let visibleResults = this.search();
    return (
      <>
        <div className="ecl-my-notifications-management__header">
          <h2 className="ecl-notifications-management__title">{this.state.title}</h2>
          <div className="ecl-subnavigation__searchform-wrapper">
            <form
              onSubmit={(e) => e.preventDefault()}
              className="ecl-search-form ecl-subnavigation__searchform"
              role="search"
            >
              <div className="ecl-form-group">
                <input
                  onChange={this.updateSearchText}
                  id="input-search"
                  className="ecl-text-input ecl-text-input--m ecl-search-form__text-input"
                  name="search"
                  type="search"
                  placeholder={this.state.translations.search_placeholder}
                />
              </div>
              <button
                className="ecl-button ecl-button--search ecl-search-form__button"
                type="submit"
                aria-label="Search"
              >
                <span
                  className="ecl-button__container"
                  dangerouslySetInnerHTML={{
                    __html: `<span class="ecl-button__label" data-ecl-label="true">${
                      this.state.translations.search_placeholder
                    }</span> ${svg(
                      'search',
                      ' ecl-icon--xs ecl-button__icon ecl-button__icon--after'
                    )}`,
                  }}
                ></span>
              </button>
            </form>
          </div>
        </div>
        {this.state.loading && (
          <CircularProgress
            style={{ top: '50%', left: '50%', position: 'fixed', zIndex: '51' }}
            className="loader"
            size={50}
          />
        )}
        <table className="ecl-my-notifications-management__table" cellPadding="0" cellSpacing="0">
          <thead>
            <tr>
              <th>{this.state.translations.notification_name}</th>
              <th>{this.state.translations.notification_status}</th>
              {this.props.unsubscribe && (
                <th>{this.state.translations.notification_unsubscribe}</th>
              )}
            </tr>
          </thead>
          <tbody>
            {visibleResults.length > 0 &&
              visibleResults.map((row, index) => {
                return (
                  <NotifRow
                    hidden={row.hidden}
                    rowIndex={index}
                    row={row}
                    key={'row-' + row.id}
                    unsubscribe={this.props.unsubscribe}
                    showUnsubscribeModal={this.showUnsubscribeModal}
                    translations={this.state.translations}
                  />
                );
              })}

            {this.state.loading === false &&
              visibleResults.length === 0 && (
                <tr>
                  <td
                    colspan={this.props.unsubscribe ? 3 : 2}
                    className="ecl-my-notifications-management__table__no-results"
                  >
                    <h3 className="ecl-my-notifications-management__table__no-results__title">
                      {this.state.translations.no_results_title}
                    </h3>
                    {this.state.translations.no_results_body}
                  </td>
                </tr>
              )}
          </tbody>
        </table>

        <div className={`ecl-viewport__modal ${!this.state.showUnsubscribeModal && 'modal-hide'}`}>
          <div className="ecl-viewport__modal__content">
            <span
              onClick={() => this.setState({ showUnsubscribeModal: false })}
              style={{ cursor: 'pointer' }}
              className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost"
            >
              <span className="ecl-link__label">
                {this.state.translations.unsubscribe_modal_close}
              </span>
              &nbsp;
              <svg
                className="ecl-icon ecl-icon--2xs ecl-link__icon"
                focusable="false"
                aria-hidden="true"
              ></svg>
            </span>{' '}
            <h3 className="ecl-activity-stream__modal__title">
              {this.state.translations.unsubscribe_modal_title}
            </h3>
            <p className="ecl-activity-stream__modal__description">
              {this.state.translations.unsubscribe_modal_desc}
            </p>
            <div className="ecl-inline-actions">
              <div className="ecl-inline-actions__items">
                <div className="ecl-inline-actions__item">
                  <span
                    onClick={this.unsubscribeConfirm}
                    style={{ cursor: 'pointer' }}
                    className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary"
                  >
                    {this.state.translations.unsubscribe_modal_confirm}
                  </span>
                </div>
                <div className="ecl-inline-actions__item">
                  <span
                    onClick={() => this.setState({ showUnsubscribeModal: false })}
                    style={{ cursor: 'pointer' }}
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
    );
  }
}

export default Overview;
