import React from "react";
import ReactDOM from "react-dom";
import annoucementsItems from '@theme/data/annoucements.data';
import Announcements from "./index";

const element = document.getElementById('react-announcements');
/*const items = element.dataset.items*/
const items = annoucementsItems

ReactDOM.render(
  <>
    <Announcements
      items={items}
    />
  </>,
  element
);
