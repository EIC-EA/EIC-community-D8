import React from "react";
import ReactDOM from "react-dom";
import EntityTree from "./index";

const elements = document.getElementsByClassName('entity-tree-reference-widget');

for (let element of elements) {
  const parentDiv = element.parentNode
  let newElement = document.createElement('div');
  newElement = parentDiv.insertBefore(newElement, element);

  ReactDOM.render(
    <EntityTree
      url={element.dataset.termsUrl}
      urlSearch={element.dataset.termsUrlSearch}
      urlChildren={element.dataset.termsUrlChildren}
      selectedTerms={element.dataset.selectedTerms}
      targetEntity={element.dataset.targetEntity}
      targetBundle={element.dataset.targetBundle}
      matchLimit={element.dataset.matchLimit ? parseInt(element.dataset.matchLimit) : 0}
      length={element.dataset.itemsToLoad ? parseInt(element.dataset.itemsToLoad) : 25}
      disableTop={parseInt(element.dataset.disableTop)}
      loadAll={parseInt(element.dataset.loadAll)}
      ignoreCurrentUser={parseInt(element.dataset.ignoreCurrentUser)}
      isRequired={parseInt(element.dataset.isRequired)}
      drupalInput={element}
      translations={JSON.parse(element.dataset.translations)}
      group={element.dataset.groupId}
      searchSpecificUsers={parseInt(element.dataset.searchSpecificUsers)}
      canCreateTag={parseInt(element.dataset.canCreateTag)}
    />,
    newElement
  );
}

