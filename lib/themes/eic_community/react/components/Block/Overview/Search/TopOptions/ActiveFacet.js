import React from 'react';
const svg = require('../../../../../svg/svg')

class SortOption extends React.Component {
  constructor(props) {
    super(props);

    this.onClick = this.onClick.bind(this);
  }

  onClick(e) {
    this.props.updateFacet(this.props.value, false, this.props.facet);
  }

  render() {
    return (
      <button onClick={this.onClick} type="button" aria-label="Dismiss" className="ecl-tag ecl-tag--removable ecl-teaser-overview__active-filters-item">
        {this.props.value[0].toUpperCase() + this.props.value.slice(1).replace(/_/g, " ")}
        <span className="ecl-tag__icon" dangerouslySetInnerHTML={{__html: svg('clear')}}/>
      </button>
    )
    // return <div onClick={this.onClick} className="ecl-teaser-overview__active-filters-item">
    //           <div className="ecl-tag ecl-tag--removable ecl-teaser-overview__active-filters-tag">{this.props.value[0].toUpperCase() + this.props.value.slice(1).replace(/_/g, " ")}
    //             <button type="button" data-value="content-type=news" className="ecl-tag__icon"  />
    //           </div>
    // </div>
  }
}

export default SortOption;
