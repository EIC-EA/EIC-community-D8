import React from 'react';
import GroupResultItem from './ResultItem/GroupResultItem';
import LibraryResultItem from './ResultItem/LibraryResultItem';
import UserListResultItem from './ResultItem/UserListResultItem';
import UserGalleryResultItem from './ResultItem/UserGalleryResultItem';
import GlobalResultItem from './ResultItem/GlobalResultItem';
import GroupProjectResultItem from './ResultItem/GroupProjectResultItem';
import DiscussionResultItem from './ResultItem/DiscussionResultItem';
import GroupEventResultItem from './ResultItem/GroupEventResultItem';
import GlobalEventResultItem from './ResultItem/GlobalEventResultItem';
import StoryResultItem from './ResultItem/StoryResultItem';
import ActivityStreamResultItem from './ResultItem/ActivityStreamResultItem';
import CircularProgress from '@material-ui/core/CircularProgress';

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        global: GlobalResultItem,
        story: StoryResultItem,
        news: StoryResultItem,
        discussion: DiscussionResultItem,
        node_event: GroupEventResultItem,
        global_event: GlobalEventResultItem,
        group: GroupResultItem,
        organisation: GroupResultItem,
        library: LibraryResultItem,
        user_gallery: UserGalleryResultItem,
        user_list: UserListResultItem,
        activity_stream: ActivityStreamResultItem,
        project: GroupProjectResultItem,
      },
    };
  }

  render() {
    if (this.props.loading) {
      return (
        <div className="ecl-teaser-overview__loader">
          <CircularProgress style={{ zIndex: '51' }} className="loader" size={50} />
        </div>
      );
    }

    if (
      Object.keys(this.props.results).length === 0 ||
      (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0)
    )
      return (
        <div>
          <h1 className="ecl-teaser-overview__title ecl-teaser-overview__title--blue">
            {this.props.translations.no_results_title}
          </h1>
          {this.props.translations.no_results_body}
        </div>
      );

    const ResultComponent = this.state.types[this.props.bundle];

    return (
      <>
        <div className={`ecl-teaser-overview__items`}>
          {this.props.results &&
            this.props.results.docs.map((value) => {
              return (
                <ResultComponent
                  currentGroupId={this.props.currentGroupId}
                  key={value.id}
                  isAnonymous={this.props.isAnonymous}
                  translations={this.props.translations}
                  result={value}
                  isGroupOwner={this.props.isGroupOwner}
                  groupAdmins={this.props.groupAdmins}
                  type={this.props.bundle}
                />
              );
            })}
        </div>
      </>
    );
  }
}

export default Results;
