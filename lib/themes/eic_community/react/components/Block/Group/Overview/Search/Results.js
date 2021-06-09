import React from 'react';
import ResultItem from "./ResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.results || Object.keys(this.props.results).length === 0)
      return <div>{this.props.translations.no_results}</div>

    return (
      <div className="ecl-teaser-overview__items">
        {this.props.results && this.props.results.docs.map((value, index) => {
          return <ResultItem isAnonymous={this.props.isAnonymous} translations={this.props.translations} result={value}/>
        })}
      </div>
    );
  }
}

export default Results;
