import React from "react";
import ReactDOM from "react-dom";

import Toggle from "./index";
window.addEventListener('DOMContentLoaded', (event) => {
  document.querySelectorAll(".ecl-toggle__container").forEach(function (element) {
    ReactDOM.render(
      <Toggle name={element.dataset.name} checked={element.dataset.checked} url={element.dataset.url}/>,
      element
    );
  });
});

