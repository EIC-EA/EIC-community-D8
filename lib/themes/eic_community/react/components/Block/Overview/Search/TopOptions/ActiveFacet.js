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
    return <div className="ecl-teaser-overview__active-filters-item">
              <span className="ecl-tag ecl-tag--removable ecl-teaser-overview__active-filters-tag">{this.props.value}
                <button onClick={this.onClick} type="button" data-value="content-type=news" className="ecl-tag__icon" dangerouslySetInnerHTML={{__html: svg('close')}} />
              </span>
    </div>
  }
}

export default SortOption;
