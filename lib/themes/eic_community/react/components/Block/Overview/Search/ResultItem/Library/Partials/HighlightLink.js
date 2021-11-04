import React from 'react';
import axios from "axios";

const svg = require('../../../../../../../svg/svg')

class HighlightLink extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: props.isHighlighted,
    };

    this.highlightContent = this.highlightContent.bind(this);
  }

  highlightContent() {
    self = this;
    axios.post('/group/' + this.props.currentGroupId + '/highlight-content/' + this.props.nodeId, {
      withCredentials: true,
    }).then(function (response) {
      const isHighlighted = response.data.action === 'flag';
      self.props.updateHighlight(isHighlighted);
      self.setState({
        isHighlighted,
      });
    });
  }

  render() {
    return (<div className={`ecl-highlight ${this.state.isHighlighted && 'ecl-highlight--is-highlighted'}`}>
      {this.props.isFlaggable ? (
        <a style={{ cursor: 'pointer' }} onClick={this.highlightContent}>
          <div dangerouslySetInnerHTML={{__html: svg('highlight', 'ecl-icon ecl-icon--m ecl-highlight__icon')}} />
          <span className="ecl-highlight__label">Highlight</span>
        </a>
      ) : (
        <>
          <div dangerouslySetInnerHTML={{__html: svg('highlight', 'ecl-icon ecl-icon--m ecl-highlight__icon')}} />
          <span className="ecl-highlight__label">Highlight</span>
        </>
      )}
    </div>);
  }
}

export default HighlightLink;
