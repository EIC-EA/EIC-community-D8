import React from 'react';
const svg = require('../../../svg/svg')

class DeleteActivityStream extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <span
      onClick={() => this.props.showDeleteModal(this.props.messageId)}
      style={{cursor: 'pointer'}}
      dangerouslySetInnerHTML={{__html: svg('clear', 'ecl-icon ecl-icon--2xs ecl-activity-stream__item__action-delete__icon')}}
      className="ecl-activity-stream__item__action-delete">
    </span>
  }
}

export default DeleteActivityStream
