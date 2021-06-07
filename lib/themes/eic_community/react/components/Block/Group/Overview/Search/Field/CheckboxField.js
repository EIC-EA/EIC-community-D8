import React from 'react';

class CheckboxField extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      checked: false
    }

    this.onChange = this.onChange.bind(this);
  }

  onChange(e, value) {
    const checked = !this.state.checked;
    this.props.updateFacet(value, checked);

    this.setState({
      checked
    })
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item-field facet-item">
        <fieldset className="ecl-form-group">
          <div onClick={e => this.onChange(e, this.props.value[0])} className="ecl-checkbox">
            <input type="checkbox" className="ecl-checkbox__input" checked={this.state.checked}/>
            <label htmlFor="topics-5" className="ecl-checkbox__label">
            <span className="ecl-checkbox__box">
            <svg className="ecl-icon ecl-icon--s ecl-checkbox__icon" focusable="false" aria-hidden="true"/>
            </span>
              <span className="facet-item__value">{this.props.value[0]}</span>
              <span className="facet-item__count">{`(${this.props.value[1]})`}</span>
            </label>
          </div>
        </fieldset>
      </div>
    );
  }
}

export default CheckboxField;
