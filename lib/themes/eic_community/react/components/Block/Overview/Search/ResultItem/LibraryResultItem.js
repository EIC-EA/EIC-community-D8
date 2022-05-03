import React from 'react';
import VideoLibraryResultItem from "./Library/VideoLibraryResultItem";
import GalleryLibraryResultItem from "./Library/GalleryLibraryResultItem";
import DocumentLibraryResultItem from "./Library/DocumentLibraryResultItem";

class LibraryResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        gallery: GalleryLibraryResultItem,
        document: DocumentLibraryResultItem,
        video: VideoLibraryResultItem,
      }
    }
  }

  render() {
    if (!this.props.result || !this.props.result.ss_global_content_type) {
      return
    }

    const ResultComponent = this.state.types[this.props.result.ss_global_content_type];

    return (<ResultComponent
      currentGroupId={this.props.currentGroupId}
      result={this.props.result}
      isGroupOwner={this.props.isGroupOwner}
      translations={this.props.translations}
      isAnonymous={this.props.isAnonymous}
    />);
  }
}

export default LibraryResultItem;
