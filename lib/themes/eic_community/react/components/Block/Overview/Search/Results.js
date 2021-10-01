import React from 'react';
import GroupResultItem from "./ResultItem/GroupResultItem";
import LibraryResultItem from "./ResultItem/LibraryResultItem";
import UserListResultItem from "./ResultItem/UserListResultItem";
import UserGalleryResultItem from "./ResultItem/UserGalleryResultItem";
import GlobalResultItem from "./ResultItem/GlobalResultItem";
import DiscussionResultItem from "./ResultItem/DiscussionResultItem";
import ActivityStreamResultItem from "./ResultItem/ActivityStreamResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        global: GlobalResultItem,
        discussion: DiscussionResultItem,
        group: GroupResultItem,
        library: LibraryResultItem,
        activity_stream: ActivityStreamResultItem,
        user_gallery: UserGalleryResultItem,
        user_list: UserListResultItem,
      }
    }
  }

  render() {
    if (Object.keys(this.props.results).length === 0 || (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0)) {
      return <React.Fragment>
        <h3 className="ecl-activity-stream__no-content-header">{this.props.translations.no_results}</h3>
        <p className="ecl-teaser-overview__no-content-message">{ this.props.translations.no_results_text }</p>
      </React.Fragment>
    }

    const ResultComponent = this.state.types[this.props.bundle];

    return (
      <div
        className={`ecl-teaser-overview ${this.props.bundle === 'columns' ? 'ecl-teaser-overview--has-columns' : ''}`}>
        <div className={`ecl-teaser-overview__items`}>
          {this.props.results && this.props.results.docs.map((value, index) => {
            return <ResultComponent
              currentGroupId={this.props.currentGroupId}
              key={value.id}
              isAnonymous={this.props.isAnonymous}
              translations={this.props.translations}
              result={value}
              isGroupOwner={this.props.isGroupOwner}
            />
          })}
        </div>
      </div>
    );
  }
}

export default Results;
