import React from 'react';
const svg = require('../../../../../svg/svg')

class SortOption extends React.Component {
  constructor(props) {
    super(props);

    this.el = React.createRef()

    this.onClick = this.onClick.bind(this);
    this.resetScroll = this.resetScroll.bind(this);
  }

  onClick() {
    this.resetScroll()
    this.props.updateFacet(this.props.value, false, this.props.facet);
  }

  resetScroll() {
    if(window.innerWidth > 768) {
      return
    }
    this.el.current.parentElement.scrollIntoView()
  }

  render() {
    return (
      <button ref={this.el} onClick={this.onClick} type="button" aria-label="Dismiss" className="ecl-tag ecl-tag--removable ecl-teaser-overview__active-filters-item">
        {this.props.value === 'my_groups'
          ? window.drupalSettings.overview.label_active_my_groups
          : this.props.value[0].toUpperCase() + this.props.value.slice(1).replace(/_/g, " ")}
        <span className="ecl-tag__icon" dangerouslySetInnerHTML={{__html: svg('clear')}}/>
      </button>
    )
  }
}

export default SortOption;
