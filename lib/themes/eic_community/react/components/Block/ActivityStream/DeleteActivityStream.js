import React from 'react';
const svg = require('../../../svg/svg')

const DeleteActivityStream = (props) => {
  if (!window.drupalSettings.overview.has_permission_delete)
    return null;

  return <span
    onClick={() => props.showDeleteModal(props.messageId)}
    style={{cursor: 'pointer'}}
    dangerouslySetInnerHTML={{__html: svg('clear', 'ecl-icon ecl-icon--2xs ecl-activity-stream__item__action-delete__icon')}}
    className="ecl-activity-stream__item__action-delete">
    </span>
}

export default DeleteActivityStream
