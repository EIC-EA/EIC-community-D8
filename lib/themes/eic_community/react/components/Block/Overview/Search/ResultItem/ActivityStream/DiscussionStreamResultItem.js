import React from 'react';

const svg = require('../../../../../../svg/svg')

class DiscussionStreamResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (<div>
        <p>{this.props.result.timestamp}</p>
        <p>{this.props.result.ss_operation_type}</p>
        <h3><a href={this.props.result.ss_path}>{this.props.result.ss_title}</a></h3>
      </div>
    );
  }
}

export default DiscussionStreamResultItem;
