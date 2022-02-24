import React from 'react';
import CommentStreamResultItem from "./ActivityStream/CommentStreamResultItem";
import DiscussionStreamResultItem from "./ActivityStream/DiscussionStreamResultItem";
import DocumentStreamResultItem from "./ActivityStream/DocumentStreamResultItem";
import EventStreamResultItem from "./ActivityStream/EventStreamResultItem";
import VideoStreamResultItem from "./ActivityStream/VideoStreamResultItem";
import WikiStreamResultItem from "./ActivityStream/WikiStreamResultItem";

class ActivityStreamResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        comment: CommentStreamResultItem,
        node_comment: CommentStreamResultItem,
        node: CommentStreamResultItem,
        discussion: DiscussionStreamResultItem,
        document: DocumentStreamResultItem,
        event: EventStreamResultItem,
        video: VideoStreamResultItem,
        gallery: DocumentStreamResultItem,
        wiki_page: WikiStreamResultItem
      }
    }
  }

  render() {
    if (!this.props.result || !this.props.result.ss_type) {
      return <></>
    }

    const ResultComponent = this.state.types[this.props.result.ss_type];

    return (<ResultComponent
      result={this.props.result}
      translations={this.props.translations}
    />);
  }
}

export default ActivityStreamResultItem;
