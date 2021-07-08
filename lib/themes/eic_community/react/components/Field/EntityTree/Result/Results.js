import React from 'react';
import ResultItem from "./ResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (this.props.results.length === 0) {
      return '';
    }

    return <React.Fragment>
      {Object.entries(this.props.results).map((value) => {
        let term = value[1];

        return <ResultItem
          values={this.props.values}
          removeChip={this.props.removeChip}
          addChip={this.props.addChip}
          key={term.tid}
          url={this.props.url}
          term={term}
        />
      })}
    </React.Fragment>;
  }

}

export default Results;
