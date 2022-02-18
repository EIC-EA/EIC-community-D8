import React from "react";
import ReactDOM from "react-dom";
import data from "./data.exemple"
import ReactNotification from 'react-notifications-component';
import Overview from "./index";

const element = document.getElementById('notif-management-overview');
// const data = JSON.parse(element.data.data)
ReactDOM.render(
  <>
    <ReactNotification />
    <Overview
      data={data}
      url={element.dataset.url}
      unsubscribe={element.dataset.unsubscribe}
      translations={element.dataset.translations}
    />
  </>,
  element
);

