import React from "react";
import ReactDOM from "react-dom";
import ShareElement from "./index.js"
import "react-notifications-component/dist/theme.css";
import ReactNotification from 'react-notifications-component';

const element = document.getElementById('react-modal-block');
const groupsUrl = element.dataset.getGroupsUrl
const endpoint = element.dataset.endpoint
const settings = window.drupalSettings.share_modal;

const Notification = () => ReactDOM.createPortal(<ReactNotification />, document.querySelector("body"))

ReactDOM.render(
  <>
    <Notification />
    <ShareElement endpoint={endpoint} groupsUrl={groupsUrl}  settings={settings}/>
  </>,
  element
);

