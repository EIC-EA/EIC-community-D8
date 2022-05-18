import React from "react";
import ReactDOM from "react-dom";
import DigestSettings from "./index";

window.addEventListener('DOMContentLoaded', () => {
  const element = document.getElementById("ecl-my-notifications-digest")
  const toogle = JSON.parse(element.dataset.toggle)
  const select = JSON.parse(element.dataset.select)
  ReactDOM.render(
    <DigestSettings toogle={toogle} select={select}/>,
    element
  );
});
