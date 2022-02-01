import React from "react";
import ReactDOM from "react-dom";

import ActivityStream from "./index";

const element = document.getElementById('activity-stream-overview');

ReactDOM.render(
  <ActivityStream
    solrUrl={element.dataset.solrUrl}
    translations={element.dataset.translations}
    sourceClass={element.dataset.sourceClass}
    datasource={element.dataset.datasource}
    currentGroup={element.dataset.currentGroup}
    isAnonymous={element.dataset.isAnonymous}
  />,
  element
);

