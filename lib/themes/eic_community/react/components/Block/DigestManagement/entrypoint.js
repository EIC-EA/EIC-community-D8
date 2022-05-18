import React from "react";
import ReactDOM from "react-dom";
import DigestSettings from "./index";

window.addEventListener('DOMContentLoaded', () => {
  const toogle = {
    title: "Email digest notifications",
    update_url: '#',
    status: true,
  }
  const select = {
    title: "Manage digest periodicity",
    update_url: '#',
    value: 'daily',
  }
  ReactDOM.render(
    <DigestSettings toogle={toogle} select={select} />,
    document.getElementById("ecl-my-notifications-digest")
  );
});
