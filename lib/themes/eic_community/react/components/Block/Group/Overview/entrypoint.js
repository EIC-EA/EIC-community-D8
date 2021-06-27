import React from "react";
import ReactDOM from "react-dom";

import Overview from "./index";

const element = document.getElementById('group-overview');
ReactDOM.render(
  <Overview
    url={element.dataset.solrUrl}
    translations={element.dataset.solrTranslations}
    isAnonymous={element.dataset.solrIsAnonymous}
    facets={element.dataset.facets}
    sorts={element.dataset.sorts}
    enableSearch={element.dataset.enableSearch}
    resultsPerPage={element.dataset.resultsPerPage}
  />,
  element
);

