import React from "react";
import ReactDOM from "react-dom";

import Overview from "./index";

const element = document.getElementById('group-overview');
console.log(element);
ReactDOM.render(
  <Overview
    datasource={element.dataset.solrDatasource}
    facets={element.dataset.solrFacets}
  />,
  element
);

