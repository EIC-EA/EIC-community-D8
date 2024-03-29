import React from "react";
import ReactDOM from "react-dom";
import ShareElement from "./index.js"
import "react-notifications-component/dist/theme.css";
import ReactNotification from 'react-notifications-component';

const elements = document.querySelectorAll('.react-recommend-content-modal-block');

elements.forEach(element => {
  const entityType = element.dataset.entityType
  const entityId = element.dataset.entityId
  const getUsersUrl = element.dataset.getUsersUrl
  const title = element.dataset.modalTitle
  const description = element.dataset.modalDescription
  const successTitle = element.dataset.modalSuccessTitle
  const successDescription = element.dataset.modalSuccessDescription
  const canRecommend = Boolean(element.dataset.canRecommend)
  const canRecommendExternalUsers = Boolean(element.dataset.canRecommendExternalUsers)
  const endpoint = element.dataset.endpoint;
    
  const Notification = () => ReactDOM.createPortal(<ReactNotification />, document.querySelector("body"))
  
  ReactDOM.render(
    <>
      <Notification />
      <ShareElement
        label={element.dataset.label}
        entityId={entityId}
        entityType={entityType}
        getUsersUrl={getUsersUrl}
        canRecommend={canRecommend}
        canRecommendExternalUsers={canRecommendExternalUsers}
        endpoint={endpoint}
        treeWidgetSettings={JSON.parse(element.dataset.treeWidgetSettings)}
        treeWidgetTranslations={JSON.parse(element.dataset.treeWidgetTranslations)}
        title={title}
        description={description}
        successTitle={successTitle}
        successDescription={successDescription}
      />
    </>,
    element
  );  
});
