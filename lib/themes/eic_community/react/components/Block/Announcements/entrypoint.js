import React from "react";
import ReactDOM from "react-dom";
import annoucementsItems from '@theme/data/annoucements.data';
import Announcements from "./index";

const element = document.getElementById('react-announcements');
/*const items = JSON.parse(element.dataset.items)*/
const items = annoucementsItems

ReactDOM.render(
  items.map((item, index) => <Announcements item={item} />),
  element
);
