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
    prefilters={JSON.parse(element.dataset.prefilters)}
    prefilterMyInterests={element.dataset.prefilterMyInterests}
    datasource={element.dataset.datasource}
    sorts={element.dataset.sorts}
    enableSearch={element.dataset.enableSearch}
    enableDateFilter={element.dataset.enableDateFilter}
    pageOptions={element.dataset.pageOptions}
    sourceClass={element.dataset.sourceClass}
    bundle={element.dataset.sourceBundle}
    layout={element.dataset.sourceLayout}
    urlSearchString={element.dataset.searchString}
    currentGroup={element.dataset.currentGroup}
    currentGroupUrl={element.dataset.currentGroupUrl}
    isGroupOwner={parseInt(element.dataset.isGroupOwner)}
    enableFacetMyGroups={element.dataset.enableFacetMyGroups}
    enableFacetInterests={element.dataset.enableFacetInterests}
    allowPagination={parseInt(element.dataset.allowPagination)}
    loadMoreNumber={parseInt(element.dataset.loadMoreNumber)}
    isSearchOverview={parseInt(element.dataset.isGroupSearchOverview) || false}
    enableInviteUserAction={parseInt(element.dataset.enableInviteUserAction) || false}
    inviteUserUrl={element.dataset.inviteUserUrl}
    groupAdmins={JSON.parse(element.dataset.groupAdmins) || []}
  />,
  element
);

