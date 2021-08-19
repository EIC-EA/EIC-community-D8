import React from 'react';
import DiscussionStreamResultItem from "./ActivityStream/DiscussionStreamResultItem";

class ActivityStreamResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        discussion: DiscussionStreamResultItem,
      }
    }
  }

  render() {
    const ResultComponent = this.state.types[this.props.result.ss_type];

    return (<ResultComponent
      result={this.props.result}
      translations={this.props.translations}
    />);
  }
}

export default ActivityStreamResultItem;
