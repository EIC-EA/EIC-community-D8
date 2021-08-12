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
    datasource={element.dataset.datasource}
    sorts={element.dataset.sorts}
    enableSearch={element.dataset.enableSearch}
    pageOptions={element.dataset.pageOptions}
    sourceClass={element.dataset.sourceClass}
    bundle={element.dataset.sourceBundle}
    layout={element.dataset.sourceLayout}
    urlSearchString={element.dataset.searchString}
    currentGroup={element.dataset.currentGroup}
    currentGroupUrl={element.dataset.currentGroupUrl}
    enableFacetMyGroups={element.dataset.enableFacetMyGroups}
    enableFacetInterests={element.dataset.enableFacetInterests}
  />,
  element
);

