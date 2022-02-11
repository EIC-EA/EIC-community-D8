import React from "react";
import ReactDOM from "react-dom";
import data from "./data.exemple"

import Overview from "./index";

const element = document.getElementById('notif-management-overview');
// const data = JSON.parse(element.data.data)
ReactDOM.render(
  <Overview data={data}/>,
  element
);

