import React from "react";
import ReactDOM from "react-dom";
import Modal from "./index.js"

const element = document.getElementById('react-modal-block');
// const groups = element.dataset.groups
const groups = [
  {"id": 0, "name": "group 1"},
  {"id": 1, "name": "group 2"},
  {"id": 2, "name": "group 3"}
]

ReactDOM.render(
  <Modal groups={groups} />,
  element
);
