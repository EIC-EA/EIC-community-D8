import React from "react";
import ReactDOM from "react-dom";
import EntityTree from "./index";

const element = document.getElementById('testy');
const parentDiv = element.parentNode
var newElement = document.createElement('div');
newElement = parentDiv.insertBefore(newElement, element);

ReactDOM.render(
  <EntityTree
    url={element.dataset.termsUrl}
  />,
  newElement
);

