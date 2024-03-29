import React from "react";
import ReactDOM from "react-dom";

import Overview from "./index";

const elements = document.getElementsByClassName("group-overview")

for (let element of elements) {
  ReactDOM.render(
    <Overview
      url={element.dataset.solrUrl}
      translations={element.dataset.solrTranslations}
      isAnonymous={element.dataset.solrIsAnonymous}
      facets={element.dataset.facets}
      userIdFromRoute={element.dataset.userIdFromRoute}
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
      enableRegistrationFilter={parseInt(element.dataset.enableRegistrationFilter) || false}
      inviteUserUrl={element.dataset.inviteUserUrl}
      groupAdmins={JSON.parse(element.dataset.groupAdmins) || []}
      overviewTitle={element.dataset.title || null}
      postContentActions={JSON.parse(element.dataset.postContentActions)}
    />,
    element
  );
}
