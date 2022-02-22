import React from "react";
import ReactDOM from "react-dom";
import data from "./data.exemple"
import ReactNotification from 'react-notifications-component';
import Overview from "./index";

window.addEventListener('DOMContentLoaded', (event) => {
  document.querySelectorAll(".ecl-my-notifications-management").forEach(function (element) {
    let items = (element.dataset.demo ? data.items : []);
    ReactDOM.render(
      <>
        <ReactNotification />
        <Overview
          data={data}
          title={element.dataset.title}
          items={items}
          url={element.dataset.url}
          unsubscribe={element.dataset.unsubscribe}
          translations={element.dataset.translations}
          demo={element.dataset.demo}
        />
      </>,
      element
    );
  });
});
