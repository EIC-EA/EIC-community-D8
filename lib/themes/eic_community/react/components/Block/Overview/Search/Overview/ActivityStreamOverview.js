import React from 'react';
import Results from "../Results";
import LoadMore from "../../../ActivityStream/LoadMore";

class ActivityStreamOverview extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (<div className="ecl-activity-stream">
      <h3 className="ecl-activity-stream__title">Latest member activity</h3>

      <Results
        bundle={this.props.bundle}
        isAnonymous={this.props.isAnonymous}
        translations={this.props.translations}
        results={this.props.results}
        datasource={this.props.datasource}
        currentGroupId={this.props.currentGroup}
        isGroupOwner={this.props.isGroupOwner}
      />
      <LoadMore
        results={this.props.results}
        translations={this.props.translations}
        changePage={this.changePage}
        numFound={this.props.total}
        page={this.props.page}
      />
    </div>)
  }
}

export default ActivityStreamOverview;
