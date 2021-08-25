import React from 'react';
import ResultItem from "./ResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);

    this.selectParent = this.selectParent.bind(this);
  }

  selectParent(checked) {
    if (!this.props.parent || this.props.parent.tid === 0) {
      return;
    }

    if (this.props.updateChip) {
      this.props.updateChip(checked, true);
    }
  }

  render() {
    if (this.props.results.length === 0) {
      return '';
    }

    return <React.Fragment>
      {Object.entries(this.props.results).map((value) => {
        let term = value[1];

        return <ResultItem
          targetEntity={this.props.targetEntity}
          targetBundle={this.props.targetBundle}
          values={this.props.values}
          removeChip={this.props.removeChip}
          addChip={this.props.addChip}
          selectParent={this.selectParent}
          key={term.tid}
          url={this.props.url}
          term={term}
          disableTop={this.props.disableTop}
        />
      })}
    </React.Fragment>;
  }

}

export default Results;
