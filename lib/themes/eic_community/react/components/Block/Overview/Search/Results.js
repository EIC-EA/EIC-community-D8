import React from 'react';
import GroupResultItem from "./ResultItem/GroupResultItem";
import UserListResultItem from "./ResultItem/UserListResultItem";
import UserGalleryResultItem from "./ResultItem/UserGalleryResultItem";
import GlobalResultItem from "./ResultItem/GlobalResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      types: {
        user_list: UserListResultItem,
        user_gallery: UserGalleryResultItem,
        group: GroupResultItem,
        global: GlobalResultItem
      }
    }
  }

  render() {
    if (Object.keys(this.props.results).length === 0 || (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0))
      return <div>{this.props.translations.no_results}</div>

    const ResultComponent = this.state.types[this.props.bundle];

    return (
      <div
        className={`ecl-teaser-overview ${this.props.bundle === 'columns' ? 'ecl-teaser-overview--has-columns' : ''}`}>
        <div className={`ecl-teaser-overview__items`}>
          {this.props.results && this.props.results.docs.map((value, index) => {
            return <ResultComponent key={value.id} isAnonymous={this.props.isAnonymous}
                                    translations={this.props.translations} result={value}/>
          })}
        </div>
      </div>
    );
  }
}

export default Results;
