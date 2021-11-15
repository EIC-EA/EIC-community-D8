import React from "react";
import ReactDOM from "react-dom";
import Modal from "./index.js"

const element = document.getElementById('react-modal-block');
const groups = JSON.parse(element.dataset.groups)
const endpoint = element.dataset.endpoint

ReactDOM.render(
  <Modal groups={groups} endpoint={endpoint} />,
  element
);
