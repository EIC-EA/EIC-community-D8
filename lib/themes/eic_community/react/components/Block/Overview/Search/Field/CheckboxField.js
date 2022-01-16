import React from 'react';

class CheckboxField extends React.Component {
  constructor(props) {
    super(props);

    this.onChange = this.onChange.bind(this);
    const mapFacetLabel = window.drupalSettings?.overview?.filter_label;
    this.facetLabel = mapFacetLabel && mapFacetLabel?.[props.facet]?.[props.value[0]]
        ? mapFacetLabel[props.facet][this.props.value[0]]
        : this.props.value[0];
  }

  onChange(e, value) {
    if (this.props.value[1] === 0) {
      return
    }

    const checked = !this.props.checked;
    this.props.updateFacet(value, checked, this.props.facet);
  }

  render() {
    let locked = this.props.value[1] === 0;

    return (
      <div className="ecl-filter-sidebar__item-field facet-item">
        <fieldset className="ecl-form-group">
          <div onClick={e => this.onChange(e, this.props.value[0])} className="ecl-checkbox">
            <input readOnly type="checkbox" className="ecl-checkbox__input" checked={this.props.checked}/>
            <label className="ecl-checkbox__label">
            <span className={`ecl-checkbox__box ${locked ? 'ecl-u-border-color-xl-grey-50' : ''}`}>
            <svg className="ecl-icon ecl-icon--s ecl-checkbox__icon" focusable="false" aria-hidden="true"/>
            </span>
              <span className={`facet-item__value ${locked ? 'ecl-u-type-color-grey-50' : ''}`}><b>{this.facetLabel[0]?.toUpperCase() + this.facetLabel.slice(1).replace(/_/g, " ")}</b></span>
              <span className={`facet-item__count ${locked ? 'ecl-u-type-color-grey-50' : ''}`}>&nbsp;{`(${this.props.value[1]})`}</span>
            </label>
          </div>
        </fieldset>
      </div>
    );
  }
}

export default CheckboxField;
