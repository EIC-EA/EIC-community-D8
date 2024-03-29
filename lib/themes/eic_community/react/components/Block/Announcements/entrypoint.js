import React from "react";
import ReactDOM from "react-dom";
import Announcements from "./index";

const element = document.getElementById('react-announcements');
const items = JSON.parse(element.dataset.items)
ReactDOM.render(
  items.map((item, index) => item.items.length > 0 && <Announcements item={item} key={index} />),
  element
);
