import React from 'react';
import GroupResultItem from "./ResultItem/GroupResultItem";
import LibraryResultItem from "./ResultItem/LibraryResultItem";
import UserListResultItem from "./ResultItem/UserListResultItem";
import UserGalleryResultItem from "./ResultItem/UserGalleryResultItem";
import GlobalResultItem from "./ResultItem/GlobalResultItem";
import DiscussionResultItem from "./ResultItem/DiscussionResultItem";
import GroupEventResultItem from "./ResultItem/GroupEventResultItem";
import GlobalEventResultItem from "./ResultItem/GlobalEventResultItem";
import StoryResultItem from "./ResultItem/StoryResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        global: GlobalResultItem,
        story: StoryResultItem,
        discussion: DiscussionResultItem,
        node_event: GroupEventResultItem,
        global_event: GlobalEventResultItem,
        group: GroupResultItem,
        library: LibraryResultItem,
        user_gallery: UserGalleryResultItem,
        user_list: UserListResultItem,
      }
    }
  }

  render() {
    if (Object.keys(this.props.results).length === 0 || (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0))
      return <div>
        <h1 className="ecl-teaser-overview__title ecl-teaser-overview__title--blue">
          {this.props.translations.no_results_title}
        </h1>
        {this.props.translations.no_results_body}
      </div>

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
