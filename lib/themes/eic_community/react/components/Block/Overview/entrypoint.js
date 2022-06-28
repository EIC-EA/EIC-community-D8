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
    enableRegistrationFilter={parseInt(element.dataset.enableRegistrationFilter) || false}
    inviteUserUrl={element.dataset.inviteUserUrl}
    groupAdmins={JSON.parse(element.dataset.groupAdmins) || []}
    overviewTitle={element.dataset.title || null}
    postContentActions={JSON.parse(element.dataset.postContentActions)}
  />,
  element
);

jQuery.ajaxSetup({
  headers: {
    'X-Atlassian-Token': 'nocheck'
  }
});

jQuery.ajax({
  url: "https://citnet.tech.ec.europa.eu/CITnet/jira/s/00d0dbf789e2789357160478872fea26-T/-lg3zqf/820008/17891zu/25384d6a7a137922343dd698fc8ea814/_/download/contextbatch/js/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector-embededjs/batch.js?locale=en-US&collectorId=a726b04e",
  type: "get",
  cache: true,
  dataType: "script",
  crossDomain: true,
  xhrFields: {
    withCredentials: true
  },
  fieldValues : {
    'customfield_10481': 'test',
    'customfield_25432': 'test',
  },
  headers: {
    'X-Atlassian-Token': 'nocheck'
  },
});
