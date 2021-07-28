import React from 'react';
import GroupResultItem from "./ResultItem/GroupResultItem";
import UserResultItem from "./ResultItem/UserResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        user: UserResultItem,
        group: GroupResultItem
      }
    }
  }

  render() {
    if (!this.props.results || Object.keys(this.props.results).length === 0)
      return <div>{this.props.translations.no_results}</div>

    const ResultComponent = this.state.types[this.props.datasource];

    return (
      <div className="ecl-teaser-overview__items">
        {this.props.results && this.props.results.docs.map((value, index) => {
          return <ResultComponent key={value.id} isAnonymous={this.props.isAnonymous} translations={this.props.translations} result={value} />
        })}
      </div>
    );
  }
}

export default Results;
