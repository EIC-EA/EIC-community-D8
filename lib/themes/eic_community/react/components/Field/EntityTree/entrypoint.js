import React from "react";
import ReactDOM from "react-dom";
import EntityTree from "./index";

const element = document.getElementById('entity-tree-reference-widget');
const parentDiv = element.parentNode
let newElement = document.createElement('div');
newElement = parentDiv.insertBefore(newElement, element);

ReactDOM.render(
  <EntityTree
    url={element.dataset.termsUrl}
    urlChildren={element.dataset.termsUrlChildren}
    selectedTerms={element.dataset.selectedTerms}
    matchLimit={element.dataset.matchLimit ? parseInt(element.dataset.matchLimit) : 0}
    length={element.dataset.itemsToLoad ? parseInt(element.dataset.itemsToLoad) : 25}
    loadAll={parseInt(element.dataset.loadAll)}
    drupalInput={element}
  />,
  newElement
);

