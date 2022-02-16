import React from 'react';
import axios from "axios";
import ReactTooltip from 'react-tooltip';

const svg = require('../../../../../../../svg/svg')

class HighlightLink extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isHighlighted: props.isHighlighted,
    };

    this.highlightContent = this.highlightContent.bind(this);
    this.overviewSettings = window.drupalSettings.overview;
  }

  highlightContent() {
    self = this;
    axios.post(`${window.drupalSettings.path.baseUrl}group/${this.props.currentGroupId}/highlight-content/${this.props.nodeId}`, {
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

    const tooltipContent = this.state.isHighlighted ? this.props.translation.unHighlight : this.props.translation.highlight

    return (<div className={`ecl-highlight ${this.state.isHighlighted && 'ecl-highlight--is-highlighted'}`}>
      {this.overviewSettings.is_power_user || this.overviewSettings.is_group_owner || this.overviewSettings.is_group_admin ? (
        <>
          <ReactTooltip backgroundColor={'#004494'} type="info" effect="solid" className='ecl-highlight__tooltip' />
          <a className={'ecl-highlight__item'} data-tip={tooltipContent} style={{ cursor: 'pointer' }} onClick={this.highlightContent}>
            <div dangerouslySetInnerHTML={{__html: svg('highlight', 'ecl-icon ecl-icon--m ecl-highlight__icon')}} />
            <span className="ecl-highlight__label">Highlight</span>
          </a>
        </>
      ) : (
        <div className={'ecl-highlight__item'}>
          <div dangerouslySetInnerHTML={{__html: svg('highlight', 'ecl-icon ecl-icon--m ecl-highlight__icon')}} />
          <span className="ecl-highlight__label">Highlight</span>
        </div>
      )}
    </div>);
  }
}

export default HighlightLink;
