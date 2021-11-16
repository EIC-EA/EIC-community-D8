import React from "react";
import ReactDOM from "react-dom";
import ShareModal from "./index.js"
import "react-notifications-component/dist/theme.css";
import ReactNotification from 'react-notifications-component';

const element = document.getElementById('react-modal-block');
const groups = JSON.parse(element.dataset.groups)
const endpoint = element.dataset.endpoint
const settings = window.drupalSettings.share_modal;

ReactDOM.render(
  <>
    <ReactNotification />
    <ShareModal groups={groups} endpoint={endpoint} settings={settings}/>
  </>,
  element
);
