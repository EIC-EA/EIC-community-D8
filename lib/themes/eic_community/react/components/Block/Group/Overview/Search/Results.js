import React from 'react';
import ResultItem from "./ResultItem";

class Results extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.results || Object.keys(this.props.results).length === 0)
      return <div>Pas de résultats</div>

    return (
      <div className="ecl-base-layout__main">
        <main>
          <div
            className="ecl-teaser-overview ecl-teaser-overview--has-compact-layout ecl-teaser-overview--has-columns">
            <div className="ecl-teaser-overview__items">
              {this.props.results && this.props.results.docs.map((value, index) => {
                return <ResultItem result={value}/>
              })}
            </div>
          </div>
        </main>
      </div>
    );
  }
}

export default Results;
