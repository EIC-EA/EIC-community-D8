import React from 'react';

const svg = require('../../../../../svg/svg')

class GroupEventResultItem extends React.Component {
  constructor(props) {
    super(props);

  }

  render() {
    return <div>{this.props.result.tm_global_title}</div>;
  }
}

export default GroupEventResultItem;
