import React from 'react';

class CheckboxField extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      checked: false,
    }

    this.onChange = this.onChange.bind(this);
  }

  onChange(e, value) {
    if (this.props.value[1] === 0) {
      return
    }

    const checked = !this.state.checked;
    this.props.updateFacet(value, checked);

    this.setState({
      checked
    })
  }

  render() {
    let locked = this.props.value[1] === 0;

    return (
      <div className="ecl-filter-sidebar__item-field facet-item">
        <fieldset className="ecl-form-group">
          <div onClick={e => this.onChange(e, this.props.value[0])} className="ecl-checkbox">
            <input readOnly type="checkbox" className="ecl-checkbox__input" checked={this.state.checked}/>
            <label className="ecl-checkbox__label">
            <span className={`ecl-checkbox__box ${locked ? 'ecl-u-border-color-xl-grey-50' : ''}`}>
            <svg className="ecl-icon ecl-icon--s ecl-checkbox__icon" focusable="false" aria-hidden="true"/>
            </span>
              <span className={`facet-item__value ${locked ? 'ecl-u-type-color-grey-50' : ''}`}><b>{this.props.value[0]}</b></span>
              <span className={`facet-item__count ${locked ? 'ecl-u-type-color-grey-50' : ''}`}>{`(${this.props.value[1]})`}</span>
            </label>
          </div>
        </fieldset>
      </div>
    );
  }
}

export default CheckboxField;
