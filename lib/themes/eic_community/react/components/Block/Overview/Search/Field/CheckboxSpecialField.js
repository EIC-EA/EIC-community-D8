import React from 'react';

class CheckboxSpecialField extends React.Component {
  constructor(props) {
    super(props);

    this.onChange = this.onChange.bind(this);
  }

  onChange(e, value) {
    if (this.props.value[1] === 0) {
      return
    }

    const checked = !this.props.checked;
    this.props.updateFacet(value, checked, this.props.facet);
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item-field facet-item">
        <fieldset className="ecl-form-group">
          <div onClick={e => this.onChange(e, this.props.value)} className="ecl-checkbox">
            <input readOnly type="checkbox" className="ecl-checkbox__input" checked={this.props.checked}/>
            <label className="ecl-checkbox__label">
            <span className={`ecl-checkbox__box`}>
            <svg className="ecl-icon ecl-icon--s ecl-checkbox__icon" focusable="false" aria-hidden="true"/>
            </span>
              <span className={`facet-item__value`}><b>{this.props.label}</b></span>
            </label>
          </div>
        </fieldset>
      </div>
    );
  }
}

export default CheckboxSpecialField;
